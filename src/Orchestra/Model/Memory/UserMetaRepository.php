<?php namespace Orchestra\Model\Memory;

use Illuminate\Container\Container;
use Orchestra\Memory\Abstractable\Handler;
use Orchestra\Memory\MemoryHandlerInterface;

class UserMetaRepository extends Handler implements MemoryHandlerInterface
{
    /**
     * Storage name.
     *
     * @var string
     */
    protected $storage = 'user';

    /**
     * Setup a new memory handler.
     *
     * @param  string                           $name
     * @param  array                            $config
     * @param  \Illuminate\Container\Container  $repository
     */
    public function __construct($name, array $config, Container $repository)
    {
        $this->repository = $repository;

        parent::__construct($name, $config);
    }

    /**
     * Initiate the instance.
     *
     * @return array
     */
    public function initiate()
    {
        return array();
    }

    /**
     * Get value from database.
     *
     * @param  string   $key
     * @return mixed
     */
    public function retrieve($key)
    {
        list($name, $userId) = explode('/user-', $key);

        $userMeta = $this->getModel()->search($name, $userId)->first();

        if (! is_null($userMeta)) {
            if (! $value = @unserialize($userMeta->value)) {
                $value = $userMeta->value;
            }

            $this->addKey($key, array(
                'id'    => $userMeta->id,
                'value' => $value,
            ));

            return $value;
        }

        return null;
    }

    /**
     * Add a finish event.
     *
     * @param  array   $items
     * @return boolean
     */
    public function finish(array $items = array())
    {
        foreach ($items as $key => $value) {
            $this->save($key, $value);
        }

        return true;
    }

    /**
     * Save user meta to memory.
     *
     * @param  mixed    $key
     * @param  mixed    $value
     * @return void
     */
    protected function save($key, $value)
    {
        $isNew = $this->isNewKey($key);

        list($name, $userId) = explode('/user-', $key);

        // We should be able to ignore this if user id is empty or checksum
        // return the same value (no change occured).
        if ($this->check($key, $value) or empty($userId)) {
            return ;
        }

        return $this->saving($name, $userId, $value, $isNew);
    }

    /**
     * Process saving the value to memory.
     *
     * @param  string   $name
     * @param  mixed    $userId
     * @param  mixed    $value
     * @param  boolean  $isNew
     * @return void
     */
    protected function saving($name, $userId, $value = null, $isNew = true)
    {
        $meta = $this->getModel()->search($name, $userId)->first();

        // Deleting a configuration is determined by ':to-be-deleted:'. It
        // would be extremely weird if that is used for other purposed.
        if (is_null($value) or $value === ':to-be-deleted:') {
            ! is_null($meta) and $meta->delete();
            return ;
        }

        // If the content is a new configuration, let push it as a insert
        // instead of an update to Eloquent.
        if (true === $isNew and is_null($meta)) {
            $meta = $this->getModel();

            $meta->name    = $name;
            $meta->user_id = $userId;
        }

        $meta->value = serialize($value);
        $meta->save();
    }

    /**
     * Get model instance.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        return $this->repository->make('Orchestra\Model\UserMeta')->newInstance();
    }
}
