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

/**
 * Created by PhpStorm.
 * User: abbadon1334
 * Date: 3/4/19
 * Time: 8:03 AM
 */

namespace atk4\ATK4DBSession;


class AppSessionHandler
{
    use \atk4\core\AppScopeTrait;
    use \atk4\core\InitializerTrait {
        init as _init;
    }
    
    public $persistence;
    
    public $gc_maxlifetime;
    
    public $gc_probability;
    
    public $php_session_options;
    
    public $session_handler;
    
    public function init()
    {
        $this->_init();
        
        $this->session_handler = new SessionHandler(
            $this->persistence ?: $this->app->db,
            $this->gc_maxlifetime,
            $this->gc_probability,
            $this->php_session_options
        );
    }
}
