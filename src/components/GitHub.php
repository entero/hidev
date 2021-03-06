<?php
/**
 * Automation tool mixed with code generator for easier continuous development
 *
 * @link      https://github.com/hiqdev/hidev
 * @package   hidev
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2015-2018, HiQDev (http://hiqdev.com/)
 */

namespace hidev\components;

use hidev\helpers\Helper;
use yii\helpers\Json;

/**
 * GitHub component.
 */
class GitHub extends \hidev\base\Component
{
    protected $_name;
    protected $_vendor;
    protected $_description;
    protected $_vendorType;

    /**
     * @var string GitHub OAuth access token
     */
    protected $_token;

    public function setFull_name($value)
    {
        list($this->_vendor, $this->_name) = explode('/', $value, 2);
    }

    public function getFull_name()
    {
        return $this->getVendor() . '/' . $this->getName();
    }

    public function setFullName($value)
    {
        return $this->setFull_name($value);
    }

    public function getFullName()
    {
        return $this->getFull_name();
    }

    public function setName($value)
    {
        $this->_name = $value;
    }

    public function getName()
    {
        if (!$this->_name) {
            $this->_name = $this->take('package')->name;
        }

        return $this->_name;
    }

    public function setVendorType($value)
    {
        $this->_vendorType = $value;
    }

    public function getVendorType()
    {
        if (!$this->_vendorType) {
            $this->_vendorType = 'org';
        }

        return $this->_vendorType;
    }

    public function setVendor($value)
    {
        $this->_vendor = $value;
    }

    public function getVendor()
    {
        if (!$this->_vendor) {
            $this->_vendor = $this->take('vendor')->name;
        }

        return $this->_vendor;
    }

    public function setDescription($value)
    {
        $this->_description = $value;
    }

    public function getDescription()
    {
        if ($this->_description === null) {
            $this->_description = $this->take('package')->getTitle();
        }

        return $this->_description;
    }

    /**
     * Create the repo on GitHub.
     * @return int exit code
     */
    public function createRepo(string $repo = null)
    {
        $end = $this->getVendorType() === 'org'
            ?'/orgs/' . $this->getVendor() . '/repos'
            : '/user/repos'
        ;
        $res = $this->request('POST', $end, [
            'name'        => $this->getName(),
            'description' => $this->getDescription(),
        ]);
        if (Helper::isResponseOk($res)) {
            echo "\ngit remote add origin git@github.com:{$this->getFullName()}.git\n";
            echo "git push -u origin master\n";
        }

        return $res;
    }

    /**
     * Clone repo from github.
     * TODO this action must be run without `start`.
     * @param string $repo full name vendor/package
     * @return int exit code
     */
    public function cloneRepo($repo)
    {
        return $this->passthru('git', ['clone', 'git@github.com:' . $repo]);
    }

    /**
     * Checks if repo exists.
     * @param string $repo full name vendor/package defaults to this repo name
     * @return int exit code
     */
    public function existsRepo($repo = null)
    {
        return static::exists($repo ?: $this->getFull_name());
    }

    /**
     * Check if repo exists.
     * @param string $repo
     * @return bool
     */
    public static function exists($repo)
    {
        return !static::exec('git', ['ls-remote', 'git@github.com:' . $repo], true);
    }

    /**
     * Creates github release.
     * @param string $release version number
     */
    public function releaseRepo($release = null)
    {
        $release = $this->take('version')->getRelease($release);
        $notes = $this->take('chkipper')->getReleaseNotes();
        $wait = $this->waitPush();
        if ($wait) {
            return $wait;
        }

        return $this->request('POST', '/repos/' . $this->getFull_name() . '/releases', [
            'tag_name'  => $release,
            'name'      => $release,
            'body'      => $notes,
        ]);
    }

    /**
     * Waits until push is actually finished.
     * TODO Check github if it really has latest local commit.
     * @return int 0 - success, 1 - failed
     */
    public function waitPush()
    {
        sleep(7);

        return 0;
    }

    public function request($method, $path, $data)
    {
        $url = 'https://api.github.com' . $path;

        return $this->passthru('curl', ['-X', $method, '-H', 'Authorization: token ' . $this->getToken(), '--data', Json::encode($data), $url]);
    }

    public function findToken()
    {
        return $_SERVER['GITHUB_TOKEN'] ?: Helper::readpassword('GitHub token:');
    }

    public function getToken()
    {
        if ($this->_token === null) {
            $this->_token = $this->findToken();
        }

        return $this->_token;
    }
}
