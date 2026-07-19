<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2019, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

$lang['email_must_be_array'] = 'ইমেইল ভ্যালিডেশন মেথডে অবশ্যই একটি অ্যারে পাঠাতে হবে।';
$lang['email_invalid_address'] = 'ভুল ইমেইল ঠিকানা: %s';
$lang['email_attachment_missing'] = 'নিম্নলিখিত ইমেইল সংযুক্তি পাওয়া যায়নি: %s';
$lang['email_attachment_unreadable'] = 'এই সংযুক্তি খোলা যাচ্ছে না: %s';
$lang['email_no_from'] = '"From" হেডার ছাড়া মেইল পাঠানো যায় না।';
$lang['email_no_recipients'] = 'আপনাকে অবশ্যই প্রাপকদের অন্তর্ভুক্ত করতে হবে: To, Cc, অথবা Bcc';
$lang['email_send_failure_phpmail'] = 'PHP mail() ব্যবহার করে ইমেইল পাঠানো যাচ্ছে না। আপনার সার্ভার হয়তো এই পদ্ধতি ব্যবহার করে মেইল পাঠানোর জন্য কনফিগার করা হয়নি।';
$lang['email_send_failure_sendmail'] = 'PHP Sendmail ব্যবহার করে ইমেইল পাঠানো যাচ্ছে না। আপনার সার্ভার হয়তো এই পদ্ধতি ব্যবহার করে মেইল পাঠানোর জন্য কনফিগার করা হয়নি।';
$lang['email_send_failure_smtp'] = 'PHP SMTP ব্যবহার করে ইমেইল পাঠানো যাচ্ছে না। আপনার সার্ভার হয়তো এই পদ্ধতি ব্যবহার করে মেইল পাঠানোর জন্য কনফিগার করা হয়নি।';
$lang['email_sent'] = 'নিম্নলিখিত প্রটোকল ব্যবহার করে আপনার মেসেজ সফলভাবে পাঠানো হয়েছে: %s';
$lang['email_no_socket'] = 'Sendmail এর জন্য একটি সকেট খোলা যাচ্ছে না। দয়া করে সেটিংস পরীক্ষা করুন।';
$lang['email_no_hostname'] = 'আপনি SMTP হোস্টনেম নির্দিষ্ট করেননি।';
$lang['email_smtp_error'] = 'নিম্নলিখিত SMTP ত্রুটি ঘটেছে: %s';
$lang['email_no_smtp_unpw'] = 'ত্রুটি: আপনাকে অবশ্যই SMTP ব্যবহারকারীর নাম এবং পাসওয়ার্ড নির্ধারণ করতে হবে।';
$lang['email_failed_smtp_login'] = 'AUTH LOGIN কমান্ড পাঠাতে ব্যর্থ হয়েছে। ত্রুটি: %s';
$lang['email_smtp_auth_un'] = 'ব্যবহারকারীর নাম যাচাই করতে ব্যর্থ হয়েছে। ত্রুটি: %s';
$lang['email_smtp_auth_pw'] = 'পাসওয়ার্ড যাচাই করতে ব্যর্থ হয়েছে। ত্রুটি: %s';
$lang['email_smtp_data_failure'] = 'ডেটা পাঠানো যাচ্ছে না: %s';
$lang['email_exit_status'] = 'এক্সিট স্ট্যাটাস কোড: %s';
