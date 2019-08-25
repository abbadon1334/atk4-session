<?php

declare(strict_types=1);

namespace atk4\ATK4DBSession;

use Exception;

class SessionHandler implements \SessionHandlerInterface
{
    /**
     * Session id prefix.
     *
     * @var string
     */
    private $session_id_prefix = 'atk4';

    /**
     * Model used for Session.
     *
     * @var \atk4\ATK4DBSession\SessionModel
     */
    private $session_model;

    /**
     * Max lifetime of a session before expire.
     *
     * @var int
     */
    private $gc_maxlifetime = 60 * 60; // one hour

    /**
     * Percentage of triggering gc.
     *
     * @var float
     */
    private $gc_trigger_probability = 1 / 1000; // gc trigger 1 over 1000 request

    /**
     * SessionHandler constructor.
     *
     * @param \atk4\data\Persistence $p                   atk4 data persistence
     * @param int                    $gc_maxlifetime      seconds until session expire
     * @param float                  $gc_probability      probability of gc for expired sessions
     * @param array                  $php_session_options options for session_start
     *
     * @throws Exception
     */
    public function __construct($p, $gc_maxlifetime = null, $gc_probability = null, $php_session_options = [])
    {
        $this->gc_maxlifetime         = $gc_maxlifetime ?: $this->gc_maxlifetime;
        $this->gc_trigger_probability = $gc_probability ?: $this->gc_trigger_probability;

        // if is not disabled
        if (false !== $this->gc_trigger_probability) {
            // calculate the number to be used later in random function;
            $this->gc_trigger_probability = 10 ** (strlen((string) $this->gc_trigger_probability)
                    - (strpos((string) $this->gc_trigger_probability, '.') + 1));
        }

        $this->session_model = new SessionModel($p);

        session_set_save_handler(
            [$this, 'open'],
            [$this, 'close'],
            [$this, 'read'],
            [$this, 'write'],
            [$this, 'destroy'],
            [$this, 'gc'],
            [$this, 'create_sid'],
            [$this, 'validateId'],
            [$this, 'updateTimestamp']
        );

        register_shutdown_function('session_write_close');

        switch (session_status()) {
            case PHP_SESSION_DISABLED:
                // @codeCoverageIgnoreStart - impossible to test
                throw new Exception(['Sessions are disabled on server']);
                // @codeCoverageIgnoreEnd
            break;

            case PHP_SESSION_NONE:
                session_start($php_session_options);
            break;

            default:
                throw new Exception('session already started, cannot start Session Handler');
                break;
        }
    }

    /**
     * Close the session.
     *
     * @see https://php.net/manual/en/sessionhandler.close.php
     *
     * The close callback works like a destructor in classes and is executed
     * after the session write callback has been called. It is also invoked
     * when session_write_close() is called.
     *
     * Return value should be TRUE for success, FALSE for failure.
     *
     * @return bool
     */
    public function close(): bool
    {
        $this->session_model->unload();

        return true;
    }

    /**
     * Return a new session ID.
     *
     * @see  https://php.net/manual/en/sessionhandler.create-sid.php
     *
     * This callback is executed when a new session ID is required. No parameters are provided, and the return value
     * should be a string that is a valid session ID for your handler.
     *
     * A session ID valid for the default session handler.
     *
     * @throws Exception
     *
     * @return string
     */
    public function create_sid(): string
    {
        $sid   = [$this->session_id_prefix];
        $sid[] = $this->create_sid_part();
        $sid[] = $this->create_sid_part();
        $sid[] = $this->create_sid_part();
        $sid[] = $this->create_sid_part();
        $sid[] = $this->create_sid_part();

        return implode('-', $sid);
    }

    /**
     * Destroy a session.
     *
     * @see  https://php.net/manual/en/sessionhandler.destroy.php
     *
     * This callback is executed when a session is destroyed with session_destroy() or with session_regenerate_id()
     * with the destroy parameter set to TRUE. Return value should be TRUE for success, FALSE for failure.
     *
     * @param string $session_id The session ID being destroyed.
     *
     * @throws \atk4\data\Exception
     * @throws \atk4\core\Exception
     *
     * @return bool
     */
    public function destroy($session_id): bool
    {
        $this->session_model->tryLoadBy('session_id', $session_id);

        if ($this->session_model->loaded()) {
            $this->session_model->delete();

            return true;
        }

        return false;
    }

    /**
     * Cleanup old sessions.
     *
     * @see  https://php.net/manual/en/sessionhandler.gc.php
     *
     * The garbage collector callback is invoked internally by PHP periodically in order to purge old session data. The
     * frequency is controlled by session.gc_probability and session.gc_divisor. The value of lifetime which is passed
     * to this callback can be set in session.gc_maxlifetime. Return value should be TRUE for success, FALSE for
     * failure.
     *
     * Sessions that have not updated for the last maxlifetime seconds will be removed.
     *
     * @param int $maxlifetime
     *
     * @return bool
     */
    public function gc($maxlifetime): bool
    {
        $this->executeGC();

        return true;
    }

    public function executeGC(): void
    {
        // thx @skondakov
        // even if is a quick operation moving here time calculation is better
        $old_datetime = date('Y-m-d H:i:s', time() - $this->gc_maxlifetime);

        $m = $this->session_model->newInstance();
        $m->addCondition('created_on', '<', $old_datetime);

        // thx @skondakov i don't know this
        $m->each('delete');
    }

    /**
     * Initialize session.
     *
     * @see https://php.net/manual/en/sessionhandler.open.php
     *
     * The open callback works like a constructor in classes and is executed
     * when the session is being opened. It is the first callback function
     * executed when the session is started automatically or manually with
     * session_start().
     *
     * Return value is TRUE for success, FALSE for failure.
     *
     * @param string $save_path    The path where to store/retrieve the session.
     * @param string $session_name The session name.
     *
     * @throws Exception
     *
     * @return bool
     */
    public function open($save_path, $session_name): bool
    {
        if (false !== $this->gc_trigger_probability) {
            if (random_int(0, $this->gc_trigger_probability) === $this->gc_trigger_probability) {
                $this->gc($this->gc_maxlifetime);
            }
        }

        return true;
    }

    /**
     * Read session data.
     *
     * @see https://php.net/manual/en/sessionhandler.read.php
     *
     * The read callback must always return a session encoded (serialized) string,
     * or an empty string if there is no data to read.This callback is called internally by PHP when the session starts
     * or when session_start() is called. Before this callback is invoked PHP will invoke the open callback. The value
     * this callback returns must be in exactly the same serialized format that was originally passed for storage to
     * the write callback.
     *
     * The value returned will be unserialized automatically by PHP and used to populate the
     * $_SESSION superglobal. While the data looks similar to serialize() please note it is a different format which is
     * specified in the session.serialize_handler ini setting.
     *
     * @param string $session_id The session id to read data for.
     *
     * @throws Exception
     *
     * @return string
     */
    public function read($session_id): string
    {
        $data = ''; // no data must return an empty string

        $this->session_model->tryLoadBy('session_id', $session_id);

        if ($this->session_model->loaded()) {
            $data = $this->session_model->get('data');
        }

        // needed even if is either model->get('data') and '' are string
        return (string) $data;
    }

    /**
     * Write session data.
     *
     * @see  https://php.net/manual/en/sessionhandler.write.php
     *
     * The write callback is called when the session needs to be saved and closed. This callback receives the current
     * session ID a serialized version the $_SESSION superglobal. The serialization method used internally by PHP is
     * specified in the session.serialize_handler ini setting.The serialized session data passed to this callback
     * should be stored against the passed session ID. When retrieving this data, the read callback must return the
     * exact value that was originally passed to the write callback.This callback is invoked when PHP shuts down or
     * explicitly when session_write_close() is called. Note that after executing this function PHP will internally
     * execute the close callback.
     *
     * Note:
     * The "write" handler is not executed until after the output stream is closed. Thus, output from debugging
     * statements in the "write" handler will never be seen in the browser. If debugging output is necessary, it is
     * suggested that the debug output be written to a file instead.
     *
     * @param string $session_id   The session id.
     * @param string $session_data
     *
     * @throws \atk4\data\Exception
     *
     * @return bool
     */
    public function write($session_id, $session_data): bool
    {
        // ** Verify if there is a real need of this tryLoad here
        //
        // $this->session_model->tryLoadBy('session_id',$session_id);
        // $this->session_model['session_id'] = $session_id;
        //
        // Model is already loaded when this method will be called
        //
        // Session handling workflow
        //
        // session_start()
        //   - session_handler->open
        //     - session_handler->read
        //     - call other methods

        // ** check if this can prevent change session_id on current session
        //
        // if all is ok this will make session_handler work as intended
        if (!$this->session_model->loaded()) {
            $this->session_model['session_id'] = $session_id;
        }

        $this->session_model['data'] = $session_data;
        $this->session_model->save();

        return true;
    }

    /**
     * Update timestamp of a session.
     *
     * return value should be true for success or false for failure
     *
     * The encoded session data. This data is the result of the PHP internally encoding the $_SESSION superglobal to a
     * serialized string and passing it as this parameter. Please note sessions use an alternative serialization
     * method.
     *
     * @param string $session_id   The session id
     * @param string $session_data
     *
     * @throws \atk4\data\Exception
     *
     * @return bool
     */
    public function updateTimestamp($session_id, $session_data): bool
    {
        return $this->write($session_id, $session_data);
    }

    /**
     * return value should be true if the session id is valid otherwise false if false is returned a new session id
     * will be generated by php internally.
     *
     * @param $session_id
     *
     * @throws Exception
     *
     * @return bool
     */
    public function validateId($session_id): bool
    {
        $this->session_model->newInstance()->tryLoadBy('session_id', $session_id);

        return !$this->session_model->loaded();
    }

    /**
     * Create an hard guessing sid with a UUID structure but with variable chunk length
     * ex :
     * [prefix][chunk(4,12)]-[chunk(4,12)]-[chunk(4,12)]-[chunk(4,12)]-[chunk(4,12)].
     *
     * @throws Exception
     *
     * @return string
     */
    private function create_sid_part(): string
    {
        $desired_output_length = random_int(4, 12);
        $bits_per_character    = 5;

        $bytes_needed       = ceil($desired_output_length * $bits_per_character / 8);
        $random_input_bytes = random_bytes((int) $bytes_needed);

        // The below is translated from function bin_to_readable in the PHP source (ext/session/session.c)
        static $hexconvtab = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ,-';

        $out = '';

        $p    = 0;
        $q    = strlen($random_input_bytes);
        $w    = 0;
        $have = 0;

        $mask = (1 << $bits_per_character) - 1;

        $chars_remaining = $desired_output_length;
        while ($chars_remaining--) {
            if ($have < $bits_per_character) {
                if ($p < $q) {
                    $byte = ord($random_input_bytes[$p++]);
                    $w |= ($byte << $have);
                    $have += 8;
                } else {
                    // Should never happen. Input must be large enough.
                    break;
                }
            }

            // consume $bits_per_character bits
            $out .= $hexconvtab[$w & $mask];
            $w >>= $bits_per_character;
            $have -= $bits_per_character;
        }

        return $out;
    }
}
