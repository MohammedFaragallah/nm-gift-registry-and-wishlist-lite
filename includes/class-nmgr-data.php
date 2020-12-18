<?php

defined('ABSPATH') || exit;

/**
 * Abstract NMGR Data Class
 *
 * Handles generic data interaction implemented by classes using the same CRUD pattern.
 */
abstract class NMGR_Data
{

    /**
     * ID for this object.
     *
     * @var int
     */
    protected $id = 0;

    /**
     * Core data for this object.
     *
     * @var array
     */
    protected $core_data = array();

    /**
     * Meta data stored as internal meta keys for object
     *
     * @var array
     */
    protected $meta_data = null;

    /**
     * 	All data for this object (merges core and meta data)
     *
     * @var array
     */
    protected $data = array();

    /**
     * Set to $data on construct so we can track and reset data if needed.
     *
     * @var array
     */
    protected $default_data = array();

    /**
     * Properties that have sub properties
     *
     * @var array
     */
    protected $parent_props = array();

    /**
     * Data changes for this object.
     *
     * @var array
     */
    protected $changes = array();

    /**
     * Whether the object has been read from the database
     *
     * This property is used to as a flag to store changes to the object in a separate array
     * rather than in the actual object data from the database thus modifying the read results.
     * It allows changes to be tracked and saved to database appropriately
     */
    protected $object_read = false;

    /**
     * Contains a reference to the database model  for this class.
     *
     * @var object
     */
    protected $db;

    /**
     * Name of the object type
     *
     * @var string
     */
    protected $object_type = 'data';

    /**
     * Default constructor.
     *
     * @param int|object|array $read ID to load from the DB (optional) or already queried data.
     */
    public function __construct($read = 0)
    {
        $this->data = array_merge($this->core_data, $this->meta_data);
        $this->default_data = $this->data;
    }

    /**
     * Get the data store.
     *
     * @return object
     */
    public function get_db()
    {
        return $this->db;
    }

    /**
     * Returns the unique ID for this object.
     *
     * @return int
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * Returns all data for this object.
     *
     * @return array
     */
    public function get_data()
    {
        return array_merge(array( 'id' => $this->get_id() ), $this->data);
    }

    /**
     * Get the core data for this object
     *
     * @return array
     */
    public function get_core_data()
    {
        return $this->core_data;
    }

    /**
     * Get the meta data for this object
     *
     * @return array
     */
    public function get_meta_data()
    {
        return $this->meta_data;
    }

    /**
     * Get the default data for this object
     *
     * @return array
     */
    public function get_default_data()
    {
        return $this->default_data;
    }

    /**
     * Get the data object type
     *
     * @return string
     */
    public function get_object_type()
    {
        return $this->object_type;
    }

    /**
     * Save should create or update based on whether the object id exists
     *
     * @return int Object id
     */
    public function save()
    {
        if ($this->db) {
            do_action('nmgr_data_before_save', $this);
            if ($this->get_id()) {
                $this->db->update($this);
            } else {
                $this->db->create($this);
            }
            do_action('nmgr_data_after_save', $this);
        }
        return $this->get_id();
    }

    /**
     * Delete an object, set the ID to 0, and return result.
     *
     * @param  bool $force_delete Should the date be deleted permanently.
     * @return boolean result
     */
    public function delete($force_delete = false)
    {
        if ($this->db) {
            $this->db->delete($this, $force_delete);
            $this->set_id(0);
            return true;
        }
        return false;
    }

    /**
     * Set ID.
     *
     * @param int $id ID.
     */
    public function set_id($id)
    {
        $this->id = absint($id);
    }

    /**
     * Set all props to default values.
     */
    public function set_defaults()
    {
        $this->data = $this->default_data;
        $this->changes = array();
        $this->set_object_read(false);
    }

    /**
     * Set object read property.
     *
     * @param boolean $read Should read?.
     */
    public function set_object_read($read = true)
    {
        $this->object_read = ( bool ) $read;
    }

    /**
     * Get object read property.
     *
     * @return boolean
     */
    public function get_object_read()
    {
        return ( bool ) $this->object_read;
    }

    /**
     * Set a collection of properties in one go
     *
     * @param array  $props Key value pairs to set. Key is the prop and should map to a setter function name.
     */
    public function set_props($props)
    {
        foreach ($props as $prop => $value) {
            $setter = "set_$prop";

            if (is_callable(array( $this, $setter ))) {
                $this->{$setter}($value);
            } else {
                $this->set_prop($prop, $value);
            }
        }
    }

    /**
     * Sets a prop for a setter method.
     *
     * This stores changes in a special array so we can track what needs saving to the DB later.
     *
     * @param string $prop Name of prop to set.
     * @param mixed  $value Value of the prop.
     */
    public function set_prop($prop, $value)
    {
        if (array_key_exists($prop, $this->data)) {
            if (true === $this->object_read) {
                $stored_value = is_callable(array( $this, "get_$prop" )) ? $this->{"get_$prop"}($value) : $this->get_prop($prop);
                if ($value !== $stored_value) {
                    $this->changes[ $prop ] = $value;
                }
            } else {
                $this->data[ $prop ] = $value;
            }
        }
    }

    /**
     * Sets a child property of a parent property
     *
     * @param type $prop Name of child property to set
     * @param type $parent Name of parent property
     * @param type $value Value of the child property to set
     */
    public function set_child_prop($prop, $parent, $value)
    {
        if (isset($this->data[ $parent ]) && array_key_exists($prop, ( array ) $this->data[ $parent ])) {
            if (true === $this->object_read) {
                if ($value !== $this->data[ $parent ][ $prop ] || (isset($this->changes[ $parent ]) && array_key_exists($prop, $this->changes[ $parent ]))) {
                    $this->changes[ $parent ][ $prop ] = $value;
                }
            } else {
                $this->data[ $parent ][ $prop ] = $value;
            }
        }
    }

    /**
     * Return data changes only.
     *
     * @return array
     */
    public function get_changes()
    {
        return $this->changes;
    }

    /**
     * Merge changes with data and clear.
     */
    public function apply_changes()
    {
        $this->data = array_replace_recursive($this->data, $this->changes);
        $this->changes = array();
    }

    /**
     * Gets a prop for a getter method.
     *
     * Gets the value from either current pending changes or from the data itself.
     *
     * @param  string $prop Name of prop to get.
     * @return mixed
     */
    public function get_prop($prop)
    {
        $value = null;

        if (array_key_exists($prop, $this->data)) {
            $value = array_key_exists($prop, $this->changes) ? $this->changes[ $prop ] : $this->data[ $prop ];
        }

        return $this->filter_prop_value($value, $prop);
    }

    /**
     * Get a child property of a parent property
     *
     * @param type $prop Name of child property
     * @param type $parent Name of parent property
     * @return mixed
     */
    public function get_child_prop($prop, $parent)
    {
        $value = null;

        if (isset($this->data[ $parent ]) && array_key_exists($prop, ( array ) $this->data[ $parent ])) {
            $value = isset($this->changes[ $parent ][ $prop ]) ? $this->changes[ $parent ][ $prop ] : $this->data[ $parent ][ $prop ];
        }
        return $this->filter_prop_value($value, $prop, $parent);
    }

    /**
     * Filter the returned value of a property
     *
     * This utility function is simply used to provide a common filter for normal
     * properties and child properties of data objects
     *
     * @param mixed $value The property's value
     * @param string $prop The property name
     * @param string $parent The name of the property's parent if it is a child property
     * @return mixed The filtered property's value
     */
    public function filter_prop_value($value, $prop, $parent = '')
    {
        $prop_name = $parent ? "{$parent}_{$prop}" : $prop;
        $value = apply_filters("nmgr_get_prop_{$prop_name}", $value, $parent, $this);
        $value = apply_filters('nmgr_get_prop', $value, $prop_name, $parent, $this);
        return $value ? $value : '';
    }

    /**
     * Get all parent properties of this object
     *
     * Parent properties are wrappers for child properties which are actually
     * true properties of the object because they are stored in the database
     * as individual properties of the object
     *
     * @return array
     */
    public function get_parent_props()
    {
        return $this->parent_props;
    }

    /**
     * Get a list of object properties that need updating based on changed status
     *
     * @param array $meta_data Object meta data e.g. Wishlist meta data, Wishlist item meta data
     * @param string $meta_type The wordpress meta type (e.g. post, user)
     */
    public function get_props_to_update($meta_data, $meta_type = 'post')
    {
        $props_to_update = array();

        // Props should be updated if they are a part of the $changed array or don't exist yet.
        foreach ($this->flatten_props($meta_data) as $meta_key => $prop) {
            if (array_key_exists($meta_key, $this->flatten_props($this->get_changes())) ||
                !metadata_exists($meta_type, $this->get_id(), $meta_key)) {
                $props_to_update[ $meta_key ] = $prop;
            }
        }

        return $props_to_update;
    }

    /**
     * Flatten object properties for one level
     *
     * This function simply exposes child properties of a parent property, prefixed with the parent key.
     * It is mainly used to prepare the object for saving in the database as parent properties are only
     * wrappers for child properties and are not saved to the database
     *
     * @param array $props Parent properties
     * @return array Flattened properties
     */
    public function flatten_props($props = array())
    {
        foreach ($props as $meta_key => $prop) {
            if (is_array($prop) && in_array($meta_key, $this->get_parent_props())) {
                foreach ($prop as $key => $value) {
                    $props[ $meta_key . '_' . $key ] = $value;
                }
                unset($props[ $meta_key ]);
            }
        }
        return $props;
    }

    /**
     * Get a key, value pair of object internal meta keys to their class properties
     *
     * This function expects the parameter $props to be a single-dimensional array
     * so the array has to be flattened first if it is multi-dimensional else child properties
     * of parent properties  in the multi-dimensional array would be ignored
     *
     * @param type $props Object properties
     * @return array
     */
    public function get_internal_meta_keys($props)
    {
        $props_to_meta_keys = array();

        // Get all internal meta properties
        $internal_meta_props = $this->flatten_props($this->get_meta_data());

        $flattened_props = $this->flatten_props(( array ) $props);

        foreach ($flattened_props as $prop => $value) {
            if (array_key_exists($prop, $internal_meta_props)) {
                $props_to_meta_keys[ $prop ] = "_$prop";
            }
        }
        return $props_to_meta_keys;
    }
}