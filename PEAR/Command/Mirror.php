<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 5                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Alexander Merz <alexmerz@php.net>                            |
// |                                                                      |
// +----------------------------------------------------------------------+
//
// $Id$

require_once "PEAR/Command/Common.php";
require_once "PEAR/Command.php";
require_once "PEAR/Remote.php";
require_once "PEAR.php";

/**
 * PEAR commands for providing file mirrors
 *
 */
class PEAR_Command_Mirror extends PEAR_Command_Common
{
    // {{{ properties

    var $commands = array(
        'download-all' => array(
            'summary' => 'Downloads each available package from master_server',
            'function' => 'doDownloadAll',
            'shortcut' => 'da',
            'options' => array(
                'channel' =>
                    array(
                    'shortopt' => 'c',
                    'doc' => 'specify a channel other than the default channel',
                    'arg' => 'CHAN',
                    )
                ),
            'doc' => '
	    Requests a list of available packages from the package server
	    (master_server) and downloads them to current working directory'
            ),
        );

    // }}}

    // {{{ constructor

    /**
     * PEAR_Command_Mirror constructor.
     *
     * @access public
     * @param object PEAR_Frontend a reference to an frontend
     * @param object PEAR_Config a reference to the configuration data
     */
    function PEAR_Command_Mirror(&$ui, &$config)
    {
        parent::PEAR_Command_Common($ui, $config);
    }

    // }}}

    // {{{ doDownloadAll()
    /**
    * retrieves a list of avaible Packages from master server
    * and downloads them
    *
    * @access public
    * @param string $command the command
    * @param array $options the command options before the command
    * @param array $params the stuff after the command name
    * @return bool true if succesful
    * @throw PEAR_Error 
    */
    function doDownloadAll($command, $options, $params)
    {
        $savechannel = $this->config->get('default_channel');
        $reg = &$this->config->getRegistry();
        $channel = isset($options['channel']) ? $options['channel'] :
            $this->config->get('default_channel');
        if (!$reg->channelExists($channel)) {
            $this->config->set('default_channel', $savechannel);
            return $this->raiseError('Channel "' . $channel . '" does not exist');
        }
        $this->config->set('default_channel', $channel);
        $this->ui->outputData('Using Channel ' . $this->config->get('default_channel'));
        $remote = &new PEAR_Remote($this->config);
        $remoteInfo = $remote->call("package.listAll");
        if (PEAR::isError($remoteInfo)) {
            return $remoteInfo;
        }
        $cmd = &PEAR_Command::factory("download", $this->config);
        if (PEAR::isError($cmd)) {
            return $cmd;
        }
        /**
         * Error handling not necessary, because already done by 
         * the download command
         */
        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $err = $cmd->run('download', array(), array_keys($remoteInfo));
        PEAR::staticPopErrorHandling();
        $this->config->set('default_channel', $savechannel);
        if (PEAR::isError($err)) {
            $this->ui->outputData($err->getMessage());
        }
        return true;
    }

    // }}}
}
