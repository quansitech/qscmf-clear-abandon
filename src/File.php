<?php

namespace ClearAbandon;


class File
{
    protected $path_name;
    protected $relative_path;
    protected $relative_path_name;
    protected $id = [];
    protected $used = false;

    public function __construct($relative_path_name, $path_name = null, $relative_path = null)
    {
        $this->initFile($relative_path_name,$path_name, $relative_path);
    }

    protected function initFile($relative_path_name, $path_name = null, $relative_path = null){
        $this->setRelativePathName($relative_path_name);
        $this->setPathName($path_name);
        $this->setRelativePath($relative_path);
        return $this;
    }

    protected function setRelativePath($relative_path){
        if (!$relative_path){
            $relative_path_name_arr = explode('/', $this->relative_path_name);
            array_pop($relative_path_name_arr);
            $relative_path = implode('/',$relative_path_name_arr) ?: '/';
        }
        $this->relative_path = $relative_path;
    }

    protected function setPathName($path_name){
        if (!$path_name){
            $path_name = ConfigHelper::getStorageFileDirName().'/'.$this->getRelativePathName();
        }
        $this->path_name = realpath($path_name);
    }

    public function getPathName(){
        return $this->path_name;
    }

    protected function setRelativePathName($relative_path_name){
        $this->relative_path_name = $relative_path_name;
    }

    public function getRelativePathName(){
        return $this->relative_path_name;
    }

    public function getRelativePath(){
        return $this->relative_path;
    }

    /**
     * @param null $id
     */
    public function setId($id): void
    {
        !in_array($id, $this->id) && $this->id[] = $id;
    }

    /**
     * @param bool $used
     */
    public function setUsed(bool $used): void
    {
        $this->used = $used;
    }

    /**
     * @return null
     */
    public function getId()
    {
        return $this->id;
    }

    public function getUsed(){
        return $this->used;
    }
}