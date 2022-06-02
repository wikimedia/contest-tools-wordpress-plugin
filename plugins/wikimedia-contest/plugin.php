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

require_once __DIR__ . '/inc/blocks/audio-submission-form/audio-form.php';
Custom_Blocks\Audio_Submission_Form\bootstrap();

require_once __DIR__ . '/inc/post-type.php';
Post_Type\bootstrap();
