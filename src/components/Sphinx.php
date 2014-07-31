<?php
/**
 * Sphinx class file.
 * @author Christoffer Lindqvist <christoffer.lindqvist@nordsoftware.com>
 * @copyright Copyright &copy; Nord Software 2014-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package nordsoftware.yii-sphinx.components
 */

namespace nordsoftware\yii_sphinx\components;

use FSphinx\FSphinxClient;

/**
 * Sphinx application component that serves as a communication layer between the app and the sphinx api.
 */
class Sphinx extends \CApplicationComponent
{
    /**
     * @var string the server host for the sphinx api client.
     */
    public $serverHost = '127.0.0.1';

    /**
     * @var int the server port for the sphinx api client.
     */
    public $serverPort = 9312;

    /**
     * @var int the default sphinx client match mode.
     */
    public $matchMode = FSphinxClient::SPH_MATCH_EXTENDED2;

    /**
     * @var array configured indices for the application.
     */
    public $indices = array();

    /**
     * @var \FSphinx\FSphinxClient|null runtime cache for the sphinx api client.
     */
    private $_client;

    /**
     * Getter for the sphinx api client instance.
     * @return FSphinxClient the client.
     */
    public function getClient()
    {
        if ($this->_client === null) {
            $this->_client = new FSphinxClient();
            $this->_client->setServer($this->serverHost, $this->serverPort);
            $this->_client->setMatchMode($this->matchMode);
        }
        return $this->_client;
    }
}