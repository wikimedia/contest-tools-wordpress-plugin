<?php
/**
 * Plugin Name: Wikimedia Contest
 * Description: Manage the Wikimedia Contests
 * Version:     1.0.0
 * Author:      Human Made
 * Author URI:  https://github.com/humanmade
 *
 * @package wikimedia-contest
 * @version 1.0.0
 */

namespace Wikimedia_Contest;

require_once __DIR__ . '/inc/workflows-triggers.php';
Workflows_Triggers\bootstrap();

require_once __DIR__ . '/inc/network-library.php';
Network_Library\bootstrap();

require_once __DIR__ . '/inc/post-type.php';
Post_Type\bootstrap();

require_once __DIR__ . '/inc/editor.php';
Editor\bootstrap();

require_once __DIR__ . '/blocks/audio-submission-form/namespace.php';
Blocks\Audio_Submission_Form\bootstrap();

