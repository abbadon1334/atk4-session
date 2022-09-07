<?php

declare(strict_types=1);

namespace Atk4\ATK4DBSession;

use DateTime;
use Exception;
use SessionHandlerInterface;
use SessionUpdateTimestampHandlerInterface;

class SessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    protected array                $session_options = [];

    private SessionModel $model;
    private ?SessionModel $entity = null;

    public function __construct(SessionModel $model, array $session_options = [])
    {
        $this->model = $model;
        $this->session_options = array_merge($this->session_options, $session_options);

        session_set_save_handler($this, true);

        switch (session_status()) {
            case PHP_SESSION_NONE:
                break;
            case PHP_SESSION_ACTIVE:
                throw new Exception('session already started, cannot start Session Handler');
            case PHP_SESSION_DISABLED:
                // @codeCoverageIgnoreStart - impossible to test
                throw new Exception('Sessions are disabled on server');
                // @codeCoverageIgnoreEnd
        }

        session_start($this->session_options);
    }

    /**
     * Closes the current session.
     *
     * This function is automatically executed when closing the session, or explicitly via session_write_close().
     * Return value should be true for success or false for failure.
     */
    public function close(): bool
    {
        if (!empty($this->entity->get())) {
            $this->entity->save();
        }

        return true;
    }

    /**
     * Destroys a session.
     *
     * Called by session_regenerate_id() (with $destroy = TRUE), session_destroy() and when session_decode() fails.
     * Return value should be true for success or false for failure.
     */
    public function destroy($id): bool
    {
        if ($this->entity->isLoaded() && $this->entity->get('session_id') === $id) {
            $this->entity->delete();
            //$this->entity = $this->model->createEntity();
        }

        $this->entity = $this->model->createEntity();

        return true;
    }

    /**
     * Cleans up expired sessions.
     *
     * Called by session_start(), based on session.gc_divisor, session.gc_probability and session.gc_maxlifetime settings.
     *
     * Return value should be true for success or false for failure.
     *
     * @return bool|int
     */
    #[\ReturnTypeWillChange]
    public function gc($max_lifetime)
    {
        try {
            $dt = (new DateTime())->modify('-' . $max_lifetime . ' SECONDS');

            $count = 0;
            foreach ((clone $this->model)->addCondition('updated_on', '<', $dt)->getIterator() as $m) {
                $m->delete();
                ++$count;
            }
        } catch (\Throwable $t) {
            return false;
        }

        return PHP_MAJOR_VERSION >= 8 ? $count : true;
    }

    /**
     * Re-initialize existing session, or creates a new one.
     * Called when a session starts or when session_start() is invoked.
     *
     * Return value should be true for success or false for failure.
     */
    public function open($path, $name): bool
    {
        $this->entity = $this->model->createEntity();

        return true;
    }

    /**
     * Reads the session data from the session storage, and returns the results.
     * Before this method is called SessionHandlerInterface::open() is invoked.
     *
     * The data returned by this method will be decoded internally by PHP using
     * the unserialization method specified in session.serialize_handler.
     * The resulting data will be used to populate the $_SESSION superglobal.
     *
     * Return value should be the session data or an empty string.
     */
    public function read($id): string
    {
        $this->entity = $this->model->tryLoadBy('session_id', $id);

        if (null === $this->entity) {
            $this->entity = $this->model->createEntity();
            $this->entity->set('session_id', $id);
        }

        // empty string in case of null is extremely important don't remove.
        return (string) ($this->entity->get('data') ?? '');
    }

    /**
     * Writes the session data to the session storage.
     *
     * SessionHandlerInterface::close() is called immediately after this function.
     * This method encodes the session data from the $_SESSION superglobal
     * to a serialized string and passes this along with the session ID to this method for storage.
     * The serialization method used is specified in the session.serialize_handler ini setting.
     *
     * Return value should be true for success or false for failure.
     */
    public function write($id, $data): bool
    {
        // write everything even empty string
        // correct settings of GC will clear unused sessions
        $this->entity->set('data', $data);

        return true;
    }

    /**
     * Validate session ID.
     *
     * This method is called when the session.use_strict_mode ini setting is set to 1
     * in order to avoid uninitialized session ID.
     * The validity of session ID is checked on starting and on regenerating
     * if strict mode is enabled.
     *
     * Return value should be true if the session ID is valid otherwise false. If false is returned a new session id will be generated.
     */
    public function validateId($id): bool
    {
        return (clone $this->model)->addCondition('session_id', $id)->tryLoadAny() !== null;
    }

    /**
     * Update timestamp of a session.
     *
     * This method is called when the session.lazy_write ini setting is set to 1
     * and no changes are made to session variables. In other words, when the session
     * need to be closed, if lazy_write mode is enabled and $_SESSION is not modified,
     * this method is called instead of SessionHandlerInterface::write()
     * in order to update session timestamp without rewriting all session data.
     *
     * Return value should be true for success or false for failure.
     */
    #[\ReturnTypeWillChange]
    public function updateTimestamp($id, $data)
    {
        try {
            $this->entity->set('data', $data);
            $this->entity->set('updated_on', new DateTime());
        } catch (\Throwable $t) {
            return false;
        }

        return true;
    }
}
