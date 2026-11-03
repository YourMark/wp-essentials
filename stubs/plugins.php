<?php
/**
 * Signatures of the optional plugins the package hooks into, for PHPStan only.
 *
 * @package YourMark\Essentials
 */

namespace Yoast\WP\SEO\Helpers {
	class Robots_Txt_Helper {
		public function add_disallow( string $user_agent, string $path ): void {}
		public function add_allow( string $user_agent, string $path ): void {}
	}
}

namespace {
	class GFCommon {
		/**
		 * @return string[]
		 */
		public static function all_caps(): array {
			return [];
		}
	}

	class Two_Factor_Core {
		/**
		 * @param int|\WP_User|null $user
		 */
		public static function is_user_using_two_factor( $user = null ): bool {
			return false;
		}
	}
}
