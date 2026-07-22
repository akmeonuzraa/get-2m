<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->extend(TestCase::class)->in('Feature');
uses(RefreshDatabase::class)->in('Feature');
