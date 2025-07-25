<?php

// FIXME svn stuff still using optc etc, won't work, needs updating!
use App\Facades\LibrenmsConfig;
use Symfony\Component\Process\Process;

if (Auth::user()->hasGlobalAdmin()) {
    if (! empty($rancid_file)) {
        echo '<div style="clear: both;">';

        print_optionbar_start('', '');

        echo "<span style='font-weight: bold;'>Config</span> &#187; ";

        if (empty($vars['rev'])) {
            echo '<span class="pagemenu-selected">';
            echo generate_link('Latest', ['page' => 'device', 'device' => $device['device_id'], 'tab' => 'showconfig']);
            echo '</span>';
        } else {
            echo generate_link('Latest', ['page' => 'device', 'device' => $device['device_id'], 'tab' => 'showconfig']);
        }

        if (LibrenmsConfig::get('rancid_repo_type') == 'svn') {
            $svn_binary = LibrenmsConfig::locateBinary('svn');
            if (is_executable($svn_binary)) {
                $sep = ' | ';

                $process = new Process([$svn_binary, 'log', '-l 8', '-q', '--xml', $rancid_file], $rancid_path);
                $process->run();
                $svnlogs_xmlstring = $process->getOutput();
                $svnlogs = [];

                $svnlogs_xml = simplexml_load_string($svnlogs_xmlstring);
                foreach ($svnlogs_xml->logentry as $svnlogentry) {
                    $rev = $svnlogentry['revision'];
                    $ts = strtotime($svnlogentry->date);
                    $svnlogs[] = ['rev' => $rev, 'date' => $ts];
                }

                $revlist = [];

                foreach ($svnlogs as $svnlog) {
                    echo $sep;
                    $revlist[] = $svnlog['rev'];

                    if ($vars['rev'] == $svnlog['rev']) {
                        echo '<span class="pagemenu-selected">';
                    }

                    $linktext = 'r' . $svnlog['rev'] . ' <small>' . date(LibrenmsConfig::get('dateformat.byminute'), $svnlog['date']) . '</small>';
                    echo generate_link($linktext, ['page' => 'device', 'device' => $device['device_id'], 'tab' => 'showconfig', 'rev' => $svnlog['rev']]);

                    if ($vars['rev'] == $svnlog['rev']) {
                        echo '</span>';
                    }

                    $sep = ' | ';
                }
            }
        }//end if
        if (LibrenmsConfig::get('rancid_repo_type') == 'git') {
            $sep = ' | ';

            $process = new Process(['git', 'log', '-n 8', '--pretty=format:%h;%ct', $rancid_file], $rancid_path);
            $process->run();
            $gitlogs_raw = explode(PHP_EOL, $process->getOutput());
            $gitlogs = [];

            foreach ($gitlogs_raw as $gl) {
                [$rev, $ts] = explode(';', $gl);
                $gitlogs[] = ['rev' => $rev, 'date' => $ts];
            }

            $revlist = [];

            foreach ($gitlogs as $gitlog) {
                echo $sep;
                $revlist[] = $gitlog['rev'];

                if ($vars['rev'] == $gitlog['rev']) {
                    echo '<span class="pagemenu-selected">';
                }

                $linktext = 'r' . $gitlog['rev'] . ' <small>' . date(LibrenmsConfig::get('dateformat.byminute'), $gitlog['date']) . '</small>';
                echo generate_link($linktext, ['page' => 'device', 'device' => $device['device_id'], 'tab' => 'showconfig', 'rev' => $gitlog['rev']]);

                if ($vars['rev'] == $gitlog['rev']) {
                    echo '</span>';
                }

                $sep = ' | ';
            }
        }

        print_optionbar_end();

        if (LibrenmsConfig::get('rancid_repo_type') == 'svn') {
            $svn_binary = LibrenmsConfig::locateBinary('svn');
            if (is_executable($svn_binary) && in_array($vars['rev'], $revlist)) {
                $process = new Process([$svn_binary, 'diff', '-c', 'r' . $vars['rev'], $rancid_file], $rancid_path);
                $process->run();
                $diff = $process->getOutput();
                if (! $diff) {
                    $text = 'No Difference';
                } else {
                    $text = $diff;
                    $previous_config = $vars['rev'] . '^';
                }
            } else {
                $fh = fopen($rancid_file, 'r');
                if ($fh === false) {
                    echo '<div class="alert alert-warning">Error: Cannot open Rancid configuration file for this device.</div>';

                    return;
                }
                if (filesize($rancid_file) == 0) {
                    echo '<div class="alert alert-warning">Error: Rancid configuration file for this device is empty.</div>';

                    return;
                }
                $text = fread($fh, filesize($rancid_file));
                fclose($fh);
            }
        } elseif (LibrenmsConfig::get('rancid_repo_type') == 'git') {
            if (in_array($vars['rev'], $revlist)) {
                $process = new Process(['git', 'diff', $vars['rev'] . '^', $vars['rev'], $rancid_file], $rancid_path);
                $process->run();
                $diff = $process->getOutput();
                if (! $diff) {
                    $text = 'No Difference';
                } else {
                    $text = $diff;
                    $previous_config = $vars['rev'] . '^';
                }
            } else {
                $fh = fopen($rancid_file, 'r');
                if ($fh === false) {
                    echo '<div class="alert alert-warning">Error: Cannot open Rancid configuration file for this device.</div>';

                    return;
                }
                if (filesize($rancid_file) == 0) {
                    echo '<div class="alert alert-warning">Error: Rancid configuration file for this device is empty.</div>';

                    return;
                }
                $text = fread($fh, filesize($rancid_file));
                fclose($fh);
            }
        }

        if (LibrenmsConfig::get('rancid_ignorecomments')) {
            $lines = explode("\n", $text);
            for ($i = 0; $i < count($lines); $i++) {
                if ($lines[$i][0] == '#') {
                    unset($lines[$i]);
                }
            }

            $text = join("\n", $lines);
        }
    } elseif (LibrenmsConfig::get('oxidized.enabled') === true && LibrenmsConfig::has('oxidized.url')) {
        // Try with hostname as set in librenms first
        $oxidized_hostname = $device['hostname'];
        // fetch info about the node and then a list of versions for that node
        $response = (new \App\ApiClients\Oxidized())->getContent('/node/show/' . $oxidized_hostname . '?format=json');
        $node_info = json_decode($response, true);
        if (! empty($node_info['last']['start'])) {
            $node_info['last']['start'] = date(LibrenmsConfig::get('dateformat.long'), strtotime($node_info['last']['start']));
        }
        if (! empty($node_info['last']['end'])) {
            $node_info['last']['end'] = date(LibrenmsConfig::get('dateformat.long'), strtotime($node_info['last']['end']));
        }
        // Try other hostname format if Oxidized request failed
        if (! $node_info) {
            // Adjust hostname based on whether domain was already in it or not
            if (strpos($oxidized_hostname, '.') !== false) {
                // Use short name
                $oxidized_hostname = strtok($device['hostname'], '.');
            } elseif (LibrenmsConfig::get('mydomain')) {
                $oxidized_hostname = $device['hostname'] . '.' . LibrenmsConfig::get('mydomain');
            }

            // Try Oxidized again with new hostname, if it has changed
            if ($oxidized_hostname != $device['hostname']) {
                $node_info = json_decode((new \App\ApiClients\Oxidized())->getContent('/node/show/' . $oxidized_hostname . '?format=json'), true);
            }
        }

        if (LibrenmsConfig::get('oxidized.features.versioning') === true) { // fetch a list of versions
            $config_versions = json_decode((new \App\ApiClients\Oxidized())->getContent('/node/version?node_full=' . (isset($node_info['full_name']) ? $node_info['full_name'] : $oxidized_hostname) . '&format=json'), true);
        }

        $config_total = 1;
        if (is_array($config_versions)) {
            $config_total = count($config_versions);
        }

        if ($config_total > 1) {
            // populate current_version
            if (isset($_POST['config'])) {
                [$oid,$date,$version] = explode('|', htmlspecialchars($_POST['config']));
                $current_config = ['oid' => $oid, 'date' => $date, 'version' => $version];
            } else { // no version selected
                $current_config = ['oid' => $config_versions[0]['oid'], 'date' => $config_versions[0]['date'], 'version' => $config_total];
            }

            // populate previous_version
            if (isset($_POST['diff'])) { // diff requested
                [$oid,$date,$version] = explode('|', $_POST['prevconfig']);
                if (isset($oid) && $oid != $current_config['oid']) {
                    $previous_config = ['oid' => $oid, 'date' => $date, 'version' => $version];
                } elseif ($current_config['version'] != 1) {  // assume previous, unless current is first config
                    foreach ($config_versions as $key => $version) {
                        if ($version['oid'] == $current_config['oid']) {
                            $prev_key = $key + 1;
                            $previous_config['oid'] = $config_versions[$prev_key]['oid'];
                            $previous_config['date'] = $config_versions[$prev_key]['date'];
                            $previous_config['version'] = $config_total - $prev_key;
                            break;
                        }
                    }
                } else {
                    print_error('No previous version, please select a different version.');
                }
            }

            if (isset($previous_config)) {
                $uri = '/node/version/diffs?node=' . $oxidized_hostname;
                if (! empty($node_info['group'])) {
                    $uri .= '&group=' . $node_info['group'];
                }
                $uri .= '&oid=' . urlencode($current_config['oid']) . '&date=' . urlencode($current_config['date']) . '&num=' . urlencode($current_config['version']) . '&oid2=' . $previous_config['oid'] . '&format=text';

                $text = (new \App\ApiClients\Oxidized())->getContent($uri); // fetch diff
            } else {
                // fetch current_version
                $text = (new \App\ApiClients\Oxidized())->getContent('/node/version/view?node=' . $oxidized_hostname . (! empty($node_info['group']) ? '&group=' . $node_info['group'] : '') . '&oid=' . urlencode($current_config['oid']) . '&date=' . urlencode($current_config['date']) . '&num=' . urlencode($current_config['version']) . '&format=text');
            }
        } else {  // just fetch the only version
            $text = (new \App\ApiClients\Oxidized())->getContent('/node/fetch/' . (! empty($node_info['group']) ? $node_info['group'] . '/' : '') . $oxidized_hostname);
        }

        if (is_array($node_info) || $config_total > 1) {
            echo '<br />
                <div class="row">
            ';

            if (is_array($node_info)) {
                echo '
                      <div class="col-sm-4">
                          <div class="panel panel-primary">
                              <div class="panel-heading">Sync status: <strong>' . $node_info['last']['status'] . '</strong></div>
                              <ul class="list-group">
                                  <li class="list-group-item"><strong>Node:</strong> ' . $node_info['name'] . '</li>
                                  <li class="list-group-item"><strong>IP:</strong> ' . $node_info['ip'] . '</li>
                                  <li class="list-group-item"><strong>Model:</strong> ' . $node_info['model'] . '</li>
                                  <li class="list-group-item" style="overflow:hidden"><strong>Last Sync:</strong> ' . $node_info['last']['end'] . ' &nbsp;<button class="btn btn-primary btn-xs" style="float: right;" name="queue-refresh"  onclick=\'refresh_oxidized_node("' . $device['hostname'] . '")\'>Refresh</button></li>
                              </ul>
                          </div>
                      </div>
                ';
            }

            if ($config_total > 1) {
                echo '
                    <div class="col-sm-8">
                        <form class="form-horizontal" action="" method="post">
                            ' . csrf_field() . '
                            <div class="form-group">
                                <label for="config" class="col-sm-2 control-label">Config version</label>
                                <div class="col-sm-6">
                                    <select id="config" name="config" class="form-control">
                ';

                $i = $config_total;
                foreach ($config_versions as $version) {
                    echo '<option value="' . $version['oid'] . '|' . $version['date'] . '|' . $config_total . '" ';
                    if ($current_config['oid'] == $version['oid']) {
                        $author = $version['author']['name'];
                        $msg = $version['message'];
                        if (isset($previous_config)) {
                            echo 'selected>+';
                        } else {
                            echo 'selected>*';
                        }
                    } elseif (isset($previous_config['oid']) && $previous_config['oid'] == $version['oid']) {
                        echo '>&nbsp;-';
                    } else {
                        echo '>&nbsp;&nbsp;';
                    }
                    echo $i . ' :: ' . $version['date'] . '</option>';
                    $i--;
                }

                echo '
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-offset-2 col-sm-6">
                                      <input type="hidden" name="prevconfig" value="';
                echo implode('|', $current_config);
                echo '">
                                      <button type="submit" class="btn btn-primary btn-sm" name="show">Show version</button>
                                      <button type="submit" class="btn btn-primary btn-sm" name="diff">Show diff</button>
                                </div>
                            </div>
                        </form>
                    </div>
                ';
            }

            echo '</div>';
        } else {
            echo '<br />';
            print_error("We couldn't retrieve the device information from Oxidized");
            if (isset($response) && preg_match('#<title>(.*)</title>#', $response, $error_matches)) {
                print_error(strip_tags($error_matches[1]));
            }
            $text = '';
        }
    }//end if

    if (! empty($author)) {
        echo '
                          <div class="panel panel-primary">
                              <div class="panel-heading">Author: <strong>' . $author . '</strong></div>';
        if (! empty($msg)) {
            echo '
                              <ul class="list-group">
                                  <li class="list-group-item"><strong>Message:</strong> ' . $msg . '</li>
                              </ul>';
        }
        echo '
                          </div>';
    }
    if (! empty($text)) {
        $language = isset($previous_config) ? 'diff' : LibrenmsConfig::getOsSetting($device['os'], 'config_highlighting', 'ios');
        $geshi = new GeSHi(htmlspecialchars_decode($text, ENT_QUOTES | ENT_HTML5), $language);
        $geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS);
        $geshi->set_overall_style('color: black;');
        // $geshi->set_line_style('color: #999999');
        echo '<div class="config">';
        echo '<input id="linenumbers" class="btn btn-primary" type="submit" value="Hide line numbers"/>';
        echo $geshi->parse_code();
        echo '</div>';
    }
}//end if

$pagetitle[] = 'Config';
