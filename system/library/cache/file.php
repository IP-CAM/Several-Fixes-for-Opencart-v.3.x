/* For some OC versions may not work. Working on a friendly version. */

<?php
namespace Opencart\System\Library\Cache;
class File {
	private int $expire;

	public function __construct(int $expire = 3600) {
		$this->expire = $expire;

		$files = glob(DIR_CACHE . 'cache.*');

		if ($files) {
			foreach ($files as $file) {
				$filename = basename($file);

				$time = substr(strrchr($file, '.'), 1);

				if ($time < time()) {
					$this->delete(substr($filename, 6, strrpos($filename, '.') - 6));
				}
			}
		}
	}

	public function get(string $key): array|string|null {
		$files = glob(DIR_CACHE . 'cache.' . basename($key) . '.*');

		if ($files) {
			$handle = fopen($files[0], 'r');

			flock($handle, LOCK_SH);

			$size = filesize($files[0]);

			if ($size > 0) {
				$data = fread($handle, $size);
			} else {
				$data = '';
			}

			flock($handle, LOCK_UN);

			fclose($handle);

			return json_decode($data, true);
		}

		return [];
	}

	public function set(string $key, array|string|null $value, int $expire = 0): void {
		$this->delete($key);

		if (!$expire) {
			$expire = $this->expire;
		}

		$file = DIR_CACHE . 'cache.' . basename($key) . '.' . (time() + $expire);

		$handle = fopen($file, 'w');

		flock($handle, LOCK_EX);

		fwrite($handle, json_encode($value));

		fflush($handle);

		flock($handle, LOCK_UN);

		fclose($handle);
	}

	public function delete(string $key): void {
		$files = glob(DIR_CACHE . 'cache.' . basename($key) . '.*');

		if ($files) {
			foreach ($files as $file) {
				if (!@unlink($file)) {
					clearstatcache(false, $file);
				}
			}
		}
	}
}
