<?php

use Anisur\ContentScheduler\Helpers\PostTypeHelper;
use PHPUnit\Framework\TestCase;

class PostTypeHelperTest extends TestCase {
	public function test_get_public_post_types_returns_slug_and_label_pairs(): void {
		$result = PostTypeHelper::get_public_post_types();

		$this->assertIsArray( $result );
		$this->assertSame( 'post', $result[0]['slug'] );
		$this->assertSame( 'Posts', $result[0]['label'] );
	}
}
