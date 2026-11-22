<?php

namespace Tests\Concurrency\Support;

use RuntimeException;

/** The requested interval overlaps an existing confirmed appointment. */
final class SlotTaken extends RuntimeException {}
