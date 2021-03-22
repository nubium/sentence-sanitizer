<?php

namespace Nubium\SentenceSanitizer;

/**
 * Interface ISentenceSanitizer.
 */
interface ISentenceSanitizer
{
	public function sanitize(string $sentence): string;
}
