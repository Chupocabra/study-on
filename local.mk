app_bash:
	@${PHP} bash
test_sec:
	@${PHP} bin/phpunit --filter Security
t_crs:
	@${PHP} bin/phpunit --filter Course