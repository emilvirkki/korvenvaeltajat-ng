<?php

namespace App;

interface ContentService {
    public function getAll(array $filters);
    public function getOne(array $filters);
}
