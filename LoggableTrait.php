<?php
namespace Klik\Loggable;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Rogervila\ArrayDiffMultidimensional;

trait LoggableTrait
{

    protected function getLogClass()
    {
        

        return  Loggable::class;
    }

    /**
     * Private variable to detect if this is an update
     * or an insert
     * @var bool
     */
    private $updating;

    /**
     * Contains all data that is valid for logging
     *
     * @var array
     */
    private $updatedData = [];
   
    /**
     * Contains all  data that is used for compare with updatedData
     *
     * @var array
     */
    private $oryginalData = [];

    /**
     * Optional reason, why this version was created
     * @var string
     */
    private $reason;

    /**
     * Flag that determines if the model allows logging at all
     * @var bool
     */
    protected $loggingEnabled = true;



    /**
     * 
     * @return object
     */
    public function getModelDataAttribute($value)
    {
        return json_decode($value);
    }


    /**
     * @return $this
     */
    public function enableLogging()
    {
        $this->loggingEnabled = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function disableLogging()
    {
        $this->loggingEnabled = false;
        return $this;
    }

    /**
     * Attribute mutator for "reason"
     * Prevent "reason" to become a database attribute of model
     *
     * @param string $value
     */
    public function setReasonAttribute($value)
    {
        $this->reason = $value;
    }

    /**
     * Initialize model events
     */
    public static function bootLoggableTrait()
    {
        static::saving(function ($model) {
            $model->loggablePreSave();
        });

        static::saved(function ($model) {
            $model->loggablePostSave();
        });

    }

    /**
     * Return all loggs of the model
     * @return MorphMany
     */
    public function loggs()
    {
        return $this->morphMany( $this->getLogClass(), 'loggable');
    }

 
    /**
     * Pre save hook to determine if logging is enabled and if we're updating
     * the model
     * @return void
     */
    protected function loggablePreSave()
    {
        if ($this->loggingEnabled === true) {
        
            $this->updatedData = $this->getDirty(); //$this->toArray()
            
            $this->updating         = $this->exists;
          
        }
    }

    /**
     * Save a new version.
     * @return void
     */
    protected function loggablePostSave()
    {
        /**
         * We'll save new loggs on updating and first creation
         */
        if (
            ( $this->loggingEnabled === true && $this->updating && $this->isValidForLogging() ) ||
            ( $this->loggingEnabled === true && !$this->updating && !is_null($this->updatedData) && count($this->updatedData))
        ) {
            // Save a new log
            $class                 = $this->getLogClass();
            $log                   = new $class();
            $log->loggable_id      = $this->getKey();
            $log->loggable_type    = get_class($this);
            $log->user_id          = $this->getAuthUserId();
            $log->table_name       = $this->table;
            $log->model_logs       = $this->getDataToSave();

            if (!empty( $this->reason )) {
                $log->reason = $this->reason;
            }

           $log->save();

           // $this->purgeOldloggs();
        }
    }

    /**
     * Delete old loggs of this model when the reach a specific count.
     * 
     * @return void
     */
    private function purgeOldloggs()
    {
        $keep = isset($this->keepOldloggs) ? $this->keepOldloggs : 0;
        
        if ((int)$keep > 0) {
            $count = $this->loggs()->count();
            
            if ($count > $keep) {
                $oldloggs = $this->loggs()
                    ->latest()
                    ->take($count)
                    ->skip($keep)
                    ->get()
                    ->each(function ($version) {
                    $version->delete();
                });
            }
        }
    }

    /**
     * Determine if a new version should be created for this model.
     *
     * @return bool
     */
    private function isValidForLogging()
    {
       
      
        return ( count(array_diff_key($this->updatedData, array_flip($this->getRemovableKeys()))) > 0 );
    }

    /**
     * @return int|null
     */
    protected function getAuthUserId()
    {
        if (Auth::check()) {
            return Auth::id();
        }
        return null;
    }

    private function getDataToSave(){
        $keys_to_unset = $this->getRemovableKeys();

        foreach($keys_to_unset as $key){
             unset($this->updatedData[$key]);
        }


        return json_encode($this->updatedData);
    }

    private function getRemovableKeys(){
        $dontLogFields = isset( $this->dontLogFields ) ? $this->dontLogFields : [];
        $removeableKeys    = array_merge($dontLogFields, [$this->getUpdatedAtColumn(), 'insert_date']);
        
        if (method_exists($this, 'getDeletedAtColumn')) {
            $removeableKeys[] = $this->getDeletedAtColumn();
        }

        return $removeableKeys;
    }


}