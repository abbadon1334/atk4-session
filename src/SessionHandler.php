<?php

declare(strict_types=1);

namespace Atk4\ATK4DBSession;

use DateTime;
use Exception;
use Ramsey\Uuid\Uuid;
use SessionHandlerInterface;
use SessionIdInterface;
use SessionUpdateTimestampHandlerInterface;

class SessionHandler implements SessionHandlerInterface, SessionIdInterface, SessionUpdateTimestampHandlerInterface
{
    protected SessionModel $model;
    private array                $session_options = [
        'use_strict_mode' => '1',
    ];

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
                throw new Exception(['Sessions are disabled on server']);
            // @codeCoverageIgnoreEnd
        }

        session_start($this->session_options);
    }

    /**
     * Close the session.
     *
     * @see  https://php.net/manual/en/sessionhandler.close.php
     *
     * @return bool <p>
     *              The return value (usually TRUE on success, FALSE on failure).
     *              Note this value is returned internally to PHP for processing.
     *              </p>
     *
     * @since 5.4
     */
    public function close(): bool
    {
        $this->model->save();

        return true;
    }

    /**
     * Return a new session ID.
     *
     * @see https://php.net/manual/en/sessionhandler.create-sid.php
     *
     * @return string <p>
     *                A session ID valid for the default session handler.
     *                </p>
     *
     * @since 5.5.1
     */
    public function create_sid(): string
    {
        return Uuid::uuid4()->toString();
    }

    /**
     * Destroy a session.
     *
     * @see https://php.net/manual/en/sessionhandler.destroy.php
     *
     * @param string $id the session ID being destroyed
     *
     * @return bool <p>
     *              The return value (usually TRUE on success, FALSE on failure).
     *              Note this value is returned internally to PHP for processing.
     *              </p>
     *
     * @since 5.4
     */
    public function destroy($id): bool
    {
        if ($this->model->loaded() && $this->model->get('session_id') === $id) {
            $this->model->delete();
            $this->model = $this->model->newInstance();
        }

        return true;
    }

    /**
     * Cleanup old sessions.
     *
     * @see https://php.net/manual/en/sessionhandler.gc.php
     *
     * @param int $max_lifetime <p>
     *                          Sessions that have not updated for
     *                          the last maxlifetime seconds will be removed.
     *                          </p>
     *
     * @return bool <p>
     *              The return value (usually TRUE on success, FALSE on failure).
     *              Note this value is returned internally to PHP for processing.
     *              </p>
     *
     * @since 5.4
     */
    public function gc($max_lifetime): bool
    {
        $dt = new DateTime();
        $dt->modify('-' . $max_lifetime . ' SECONDS');

        $model = (clone $this->model)->unload();
        $model->addCondition('updated_on', '<', $dt->format('Y-m-d H:i:s'));
        $model->each(function (SessionModel $m) {
            $m->delete();
        });

        return true;
    }

    /**
     * Initialize session.
     *
     * @see https://php.net/manual/en/sessionhandler.open.php
     *
     * @param string $path the path where to store/retrieve the session
     * @param string $name the session name
     *
     * @return bool <p>
     *              The return value (usually TRUE on success, FALSE on failure).
     *              Note this value is returned internally to PHP for processing.
     *              </p>
     *
     * @since 5.4
     */
    public function open($path, $name): bool
    {
        return true;
    }

    /**
     * Read session data.
     *
     * @see https://php.net/manual/en/sessionhandler.read.php
     *
     * @param string $id the session id to read data for
     *
     * @return string <p>
     *                Returns an encoded string of the read data.
     *                If nothing was read, it must return an empty string.
     *                Note this value is returned internally to PHP for processing.
     *                </p>
     *
     * @since 5.4
     */
    public function read($id): string
    {
        $model = $this->model->newInstance()->addCondition('session_id', $id);
        $this->model = $model->tryLoadAny();

        return $this->model->loaded() ? (string) $this->model->get('data') : '';
    }

    /**
     * Write session data.
     *
     * @see https://php.net/manual/en/sessionhandler.write.php
     *
     * @param string $id   the session id
     * @param string $data <p>
     *                     The encoded session data. This data is the
     *                     result of the PHP internally encoding
     *                     the $_SESSION superglobal to a serialized
     *                     string and passing it as this parameter.
     *                     Please note sessions use an alternative serialization method.
     *                     </p>
     *
     * @return bool <p>
     *              The return value (usually TRUE on success, FALSE on failure).
     *              Note this value is returned internally to PHP for processing.
     *              </p>
     *
     * @since 5.4
     */
    public function write($id, $data): bool
    {
        $this->model->set('data', $data);

        return true;
    }

    /**
     * Validate session id.
     *
     * @param string $id The session id
     *
     * @return bool <p>
     *              Note this value is returned internally to PHP for processing.
     *              </p>
     */
    public function validateId($id): bool
    {
        $model = (clone $this->model)->newInstance()->addCondition('session_id', $id)->tryLoadAny();

        return $model->tryLoadAny()->loaded();
    }

    /**
     * Update timestamp of a session.
     *
     * @param string $id   The session id
     * @param string $data <p>
     *                     The encoded session data. This data is the
     *                     result of the PHP internally encoding
     *                     the $_SESSION superglobal to a serialized
     *                     string and passing it as this parameter.
     *                     Please note sessions use an alternative serialization method.
     *                     </p>
     *
     * @return bool
     */
    public function updateTimestamp($id, $data)
    {
        $this->model->set('data', $data);
        $this->model->set('updated_on', new DateTime());

        return true;
    }
}
