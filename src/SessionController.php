<?php
/**
 * Copyright (c) 2019.
 *
 * Francesco "Abbadon1334" Danti <fdanti@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */

namespace atk4\ATK4DBSession;

class SessionController implements \SessionHandlerInterface
{
    
    /**
     * _SESSION id
     *
     * @var string
     */
    private $session_id = null;
    
    /**
     * Model used for Session
     *
     * @var atk4\DBSession\DBSessionModel
     */
    private $session_model;
    
    public function __construct($p)
    {
        $this->session_model = new SessionModel($p);
        
        session_set_save_handler($this,false);
    
        @session_start();
        
        register_shutdown_function(function() {
            session_commit();
        });
    }
    
    /**
     * Close the session
     *
     * @link https://php.net/manual/en/sessionhandler.close.php
     *
     * The close callback works like a destructor in classes and is executed
     * after the session write callback has been called. It is also invoked
     * when session_write_close() is called.
     *
     * Return value should be TRUE for success, FALSE for failure.
     *
     * @return bool
     */
    public function close()
    {
        $this->session_model->unload();
        return true;
    }
    
    /**
     * Return a new session ID
     *
     * @link  https://php.net/manual/en/sessionhandler.create-sid.php
     *
     * This callback is executed when a new session ID is required. No parameters are provided, and the return value
     * should be a string that is a valid session ID for your handler.
     *
     * A session ID valid for the default session handler.
     *
     * @return string
     * @since 5.5.1
     */
//    public function create_sid()
//    {
//        d(__METHOD__);
//        $sid = parent::create_sid();
//        d($sid);
//        return $sid;
//    }
    
    /**
     * Destroy a session
     *
     * @link  https://php.net/manual/en/sessionhandler.destroy.php
     *
     * This callback is executed when a session is destroyed with session_destroy() or with session_regenerate_id()
     * with the destroy parameter set to TRUE. Return value should be TRUE for success, FALSE for failure.
     *
     * @param string $session_id The session ID being destroyed.
     *
     * @return bool
     */
    public function destroy($session_id)
    {
        return true;
    }
    
    /**
     * Cleanup old sessions
     *
     * @link  https://php.net/manual/en/sessionhandler.gc.php
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
    public function gc($maxlifetime)
    {
    }
    
    /**
     * Initialize session
     *
     * @link https://php.net/manual/en/sessionhandler.open.php
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
     * @return bool
     */
    public function open($save_path, $session_name)
    {
        return true;
    }
    
    
    /**
     * Read session data
     *
     * @link https://php.net/manual/en/sessionhandler.read.php
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
     * @return string
     * @throws \Exception
     */
    public function read($session_id)
    {
        $this->session_model->tryLoadBy('session_id', $session_id);
        
        // if not present must return an empty string.
        if (!$this->session_model->loaded()) {
            return '';
        }
        
        return $this->session_model->get('data');
    }
    
    /**
     * Write session data
     *
     * @link  https://php.net/manual/en/sessionhandler.write.php
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
     * @param string $session_id The session id.
     * @param string $session_data
     *
     * @return bool
     * @throws \atk4\data\Exception
     */
    public function write($session_id, $session_data)
    {
        $this->session_model->set('data', $session_data);
        $this->session_model->set('timestamp', new \DateTime());
        $this->session_model->save();
        
        return true;
    }
    
    /**
     * Update timestamp of a session
     *
     * return value should be true for success or false for failure
     *
     * The encoded session data. This data is the result of the PHP internally encoding the $_SESSION superglobal to a
     * serialized string and passing it as this parameter. Please note sessions use an alternative serialization
     * method.
     *
     * @param string $session_id The session id
     * @param string $session_data
     *
     * @return bool
     */

//    public function updateTimestamp($sessionId, $sessionData)
//    {
//        d(__METHOD__, [func_get_args()]);
//    }
    
    /**
     * return value should be true if the session id is valid otherwise false if false is returned a new session id
     * will be generated by php internally
     *
     * @param string $sessionId
     *
     * @return bool|void
     */
//    public function validateId($sessionId)
//    {
//        d(__METHOD__, [func_get_args()]);
//    }

    
    public function getModelData()
    {
        return $this->session_model->get();
    }

}
