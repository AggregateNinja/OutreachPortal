<?php
/**
 * Created by PhpStorm.
 * User: Ed Bossmeyer
 * Date: 9/26/14
 * Time: 10:13 AM
 */
if (!isset($_SESSION)) {
    session_start();
}



abstract class SearchCreator {

    protected abstract function doSearch(array $data = null);

    public function startFactory($data) {
        return $this->doSearch($data);
    }

} 