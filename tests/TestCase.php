<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Les données de référence (rôles, permissions, types, catégories) sont
     * présentes dans tous les tests, comme dans un environnement réel.
     */
    protected bool $seed = true;
}
