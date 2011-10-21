<?php

/**
 * This file is used in conjunction with the 'Simple-LinkedIn' class, demonstrating 
 * the basic functionality and usage of the library.
 * 
 * COPYRIGHT:
 *   
 * Copyright (C) 2011, fiftyMission Inc.
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a 
 * copy of this software and associated documentation files (the "Software"), 
 * to deal in the Software without restriction, including without limitation 
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, 
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in 
 * all copies or substantial portions of the Software.  
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING 
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS 
 * IN THE SOFTWARE.  
 *
 * SOURCE CODE LOCATION:
 * 
 *   http://code.google.com/p/simple-linkedinphp/
 *    
 * REQUIREMENTS:
 * 
 * 1. You must have cURL installed on the server and available to PHP.
 * 2. You must be running PHP 5+.  
 *  
 * QUICK START:
 * 
 * There are two files needed to enable LinkedIn API functionality from PHP; the
 * stand-alone OAuth library, and the Simple-LinkedIn library. The latest 
 * version of the stand-alone OAuth library can be found on Google Code:
 * 
 *   http://code.google.com/p/oauth/
 * 
 * The latest versions of the Simple-LinkedIn library and this demonstation 
 * script can be found here:
 * 
 *   http://code.google.com/p/simple-linkedinphp/
 *   
 * Install these two files on your server in a location that is accessible to 
 * this demo script. Make sure to change the file permissions such that your 
 * web server can read the files.
 * 
 * Next, make sure the path to the LinkedIn class below is correct.
 * 
 * Finally, read and follow the 'Quick Start' guidelines located in the comments
 * of the Simple-LinkedIn library file.   
 *
 * @version 3.1.1 - July 12, 2011
 * @author Paul Mennega <paul@fiftymission.net>
 * @copyright Copyright 2011, fiftyMission Inc. 
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License 
 */

/**
 * Session existance check.
 * 
 * Helper function that checks to see that we have a 'set' $_SESSION that we can
 * use for the demo.   
 */ 
function oauth_session_exists() {
  if((is_array($_SESSION)) && (array_key_exists('oauth', $_SESSION))) {
    return TRUE;
  } else {
    return FALSE;
  }
}

try {
  // include the LinkedIn class
  require_once('../linkedin_3.1.1.class.php');
  
  // start the session
  if(!session_start()) {
    throw new LinkedInException('This script requires session support, which appears to be disabled according to session_start().');
  }
  
  // display constants
  $API_CONFIG = array(
    'appKey'       => '<your application key here>',
	  'appSecret'    => '<your application secret here>',
	  'callbackUrl'  => NULL 
  );
  define('CONNECTION_COUNT', 20);
  define('PORT_HTTP', '80');
  define('PORT_HTTP_SSL', '443');
  define('UPDATE_COUNT', 10);

  // set index
  $_REQUEST[LINKEDIN::_GET_TYPE] = (isset($_REQUEST[LINKEDIN::_GET_TYPE])) ? $_REQUEST[LINKEDIN::_GET_TYPE] : '';
  switch($_REQUEST[LINKEDIN::_GET_TYPE]) {
    case 'initiate':
      /**
       * Handle user initiated LinkedIn connection, create the LinkedIn object.
       */
        
      // check for the correct http protocol (i.e. is this script being served via http or https)
      if($_SERVER['HTTPS'] == 'on') {
        $protocol = 'https';
      } else {
        $protocol = 'http';
      }
      
      // set the callback url
      $API_CONFIG['callbackUrl'] = $protocol . '://' . $_SERVER['SERVER_NAME'] . ((($_SERVER['SERVER_PORT'] != PORT_HTTP) || ($_SERVER['SERVER_PORT'] != PORT_HTTP_SSL)) ? ':' . $_SERVER['SERVER_PORT'] : '') . $_SERVER['PHP_SELF'] . '?' . LINKEDIN::_GET_TYPE . '=initiate&' . LINKEDIN::_GET_RESPONSE . '=1';
      $OBJ_linkedin = new LinkedIn($API_CONFIG);
      
      // check for response from LinkedIn
      $_GET[LINKEDIN::_GET_RESPONSE] = (isset($_GET[LINKEDIN::_GET_RESPONSE])) ? $_GET[LINKEDIN::_GET_RESPONSE] : '';
      if(!$_GET[LINKEDIN::_GET_RESPONSE]) {
        // LinkedIn hasn't sent us a response, the user is initiating the connection
        
        // send a request for a LinkedIn access token
        $response = $OBJ_linkedin->retrieveTokenRequest();
        if($response['success'] === TRUE) {
          // store the request token
          $_SESSION['oauth']['linkedin']['request'] = $response['linkedin'];
          
          // redirect the user to the LinkedIn authentication/authorisation page to initiate validation.
          header('Location: ' . LINKEDIN::_URL_AUTH . $response['linkedin']['oauth_token']);
        } else {
          // bad token request
          echo "Request token retrieval failed:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response, TRUE) . "</pre><br /><br />LINKEDIN OBJ:<br /><br /><pre>" . print_r($OBJ_linkedin, TRUE) . "</pre>";
        }
      } else {
        // LinkedIn has sent a response, user has granted permission, take the temp access token, the user's secret and the verifier to request the user's real secret key
        $response = $OBJ_linkedin->retrieveTokenAccess($_SESSION['oauth']['linkedin']['request']['oauth_token'], $_SESSION['oauth']['linkedin']['request']['oauth_token_secret'], $_GET['oauth_verifier']);
        if($response['success'] === TRUE) {
          // the request went through without an error, gather user's 'access' tokens
          $_SESSION['oauth']['linkedin']['access'] = $response['linkedin'];
          
          // set the user as authorized for future quick reference
          $_SESSION['oauth']['linkedin']['authorized'] = TRUE;
            
          // redirect the user back to the demo page
          header('Location: ' . $_SERVER['PHP_SELF']);
        } else {
          // bad token access
          echo "Access token retrieval failed:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response, TRUE) . "</pre><br /><br />LINKEDIN OBJ:<br /><br /><pre>" . print_r($OBJ_linkedin, TRUE) . "</pre>";
        }
      }
      break;
      
    case 'revoke':
      /**
       * Handle authorization revocation.
       */
                    
      // check the session
      if(!oauth_session_exists()) {
        throw new LinkedInException('This script requires session support, which doesn\'t appear to be working correctly.');
      }
      
      $OBJ_linkedin = new LinkedIn($API_CONFIG);
      $OBJ_linkedin->setTokenAccess($_SESSION['oauth']['linkedin']['access']);
      $response = $OBJ_linkedin->revoke();
      if($response['success'] === TRUE) {
        // revocation successful, clear session
        session_unset();
        $_SESSION = array();
        if(session_destroy()) {
          // session destroyed
          header('Location: ' . $_SERVER['PHP_SELF']);
        } else {
          // session not destroyed
          echo "Error clearing user's session";
        }
      } else {
        // revocation failed
        echo "Error revoking user's token:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response, TRUE) . "</pre><br /><br />LINKEDIN OBJ:<br /><br /><pre>" . print_r($OBJ_linkedin, TRUE) . "</pre>";
      }
      break; 
       
  	case 'followCompany':
      /**
       * Handle company 'follows'.
       */
                    
      // check the session
      if(!oauth_session_exists()) {
        throw new LinkedInException('This script requires session support, which doesn\'t appear to be working correctly.');
      }
      
      $OBJ_linkedin = new LinkedIn($API_CONFIG);
      $OBJ_linkedin->setTokenAccess($_SESSION['oauth']['linkedin']['access']);
      if(!empty($_GET['nCompanyId'])) {
        $response = $OBJ_linkedin->followCompany($_GET['nCompanyId']);
        if($response['success'] === TRUE) {
          // company 'followed'
          header('Location: ' . $_SERVER['PHP_SELF']);
        } else {
          // problem with 'follow'
          echo "Error 'following' company:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response, TRUE) . "</pre><br /><br />LINKEDIN OBJ:<br /><br /><pre>" . print_r($OBJ_linkedin, TRUE) . "</pre>";
        }
      } else {
        echo "You must supply a company ID to 'follow' a company.";
      }
      break;

    case 'unfollowCompany':
      /**
       * Handle company 'unfollows'.
       */
                    
      // check the session
      if(!oauth_session_exists()) {
        throw new LinkedInException('This script requires session support, which doesn\'t appear to be working correctly.');
      }
      
      $OBJ_linkedin = new LinkedIn($API_CONFIG);
      $OBJ_linkedin->setTokenAccess($_SESSION['oauth']['linkedin']['access']);
      if(!empty($_GET['nCompanyId'])) {
        $response = $OBJ_linkedin->unfollowCompany($_GET['nCompanyId']);
        if($response['success'] === TRUE) {
          // company 'unfollowed'
          header('Location: ' . $_SERVER['PHP_SELF']);
        } else {
          // problem with 'unfollow'
          echo "Error 'unfollowing' company:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response, TRUE) . "</pre><br /><br />LINKEDIN OBJ:<br /><br /><pre>" . print_r($OBJ_linkedin, TRUE) . "</pre>";
        }
      } else {
        echo "You must supply a company ID to 'unfollow' a company.";
      }
      break;
      
    default:
      // nothing being passed back, display demo page
      
      // check PHP version
      if(version_compare(PHP_VERSION, '5.0.0', '<')) {
        throw new LinkedInException('You must be running version 5.x or greater of PHP to use this library.'); 
      } 
      
      // check for cURL
      if(extension_loaded('curl')) {
        $curl_version = curl_version();
        $curl_version = $curl_version['version'];
      } else {
        throw new LinkedInException('You must load the cURL extension to use this library.'); 
      }
      ?>
      <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
      <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
        <head>
          <title>Simple-LinkedIn Demo &gt; Company</title>
          <meta name="author" content="Paul Mennega <paul@fiftymission.net>" />
          <meta name="copyright" content="Copyright 2010 - 2011, fiftyMission Inc." />
          <meta name="license" content="http://www.opensource.org/licenses/mit-license.php" />
          <meta name="description" content="A demonstration page for the Simple-LinkedIn PHP class." />
          <meta name="keywords" content="simple-linkedin,php,linkedin,api,class,library" />
          <meta name="medium" content="mult" />
          <meta name="viewport" content="width=device-width" />
          <meta http-equiv="Content-Language" content="en" />
          <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
          <style>
            body {font-family: Courier, monospace; font-size: 0.8em;}
            pre {font-family: Courier, monospace; font-size: 0.8em;}
          </style>
        </head>
        <body>
          <h1><a href="../demo.php ">Simple-LinkedIn Demo</a> &gt; <a href="<?php echo $_SERVER['PHP_SELF'];?>">Company</a></h1>
          
          <p>Copyright 2010 - 2011, Paul Mennega, fiftyMission Inc. &lt;paul@fiftymission.net&gt;</p>
          
          <p>Released under the MIT License - http://www.opensource.org/licenses/mit-license.php</p>
          
          <p>Full source code for both the Simple-LinkedIn class and this demo script can be found at:</p>
          
          <ul>
            <li><a href="http://code.google.com/p/simple-linkedinphp/">http://code.google.com/p/simple-linkedinphp/</a></li>
          </ul>          

          <hr />
          
          <p style="font-weight: bold;">Demo using: Simple-LinkedIn v<?php echo LINKEDIN::_VERSION;?>, cURL v<?php echo $curl_version;?>, PHP v<?php echo phpversion();?></p>
          
          <ul>
            <li>Please note: The Simple-LinkedIn class requires PHP 5+</li>
          </ul>
          
          <hr />
          
          <?php
          $_SESSION['oauth']['linkedin']['authorized'] = (isset($_SESSION['oauth']['linkedin']['authorized'])) ? $_SESSION['oauth']['linkedin']['authorized'] : FALSE;
          if($_SESSION['oauth']['linkedin']['authorized'] === TRUE) {
            ?>
            <ul>
              <li><a href="#manage">Manage LinkedIn Authorization</a></li>
              <li><a href="demo.php#application">Application Information</a></li>
              <li><a href="demo.php#profile">Your Profile</a></li>
              <li><a href="#company">Company API</a>
                <ul>
                  <li><a href="#companySpecific">Specific Company</a></li>
                  <li><a href="#companyFollowed">Followed Companies</a></li>
                  <li><a href="#companySuggested">Suggested Companies</a></li>
                  <li><a href="#companySearch">Company Search</a></li>
                </ul>
              </li>
            </ul>
            <?php
          } else {
            ?>
            <ul>
              <li><a href="#manage">Manage LinkedIn Authorization</a></li>
            </ul>
            <?php
          }
          ?>
          
          <hr />
          
          <h2 id="manage">Manage LinkedIn Authorization:</h2>
          <?php
          if($_SESSION['oauth']['linkedin']['authorized'] === TRUE) {
            // user is already connected
            $OBJ_linkedin = new LinkedIn($API_CONFIG);
            $OBJ_linkedin->setTokenAccess($_SESSION['oauth']['linkedin']['access']);
            ?>
            <form id="linkedin_revoke_form" action="<?php echo $_SERVER['PHP_SELF'];?>" method="get">
              <input type="hidden" name="<?php echo LINKEDIN::_GET_TYPE;?>" id="<?php echo LINKEDIN::_GET_TYPE;?>" value="revoke" />
              <input type="submit" value="Revoke Authorization" />
            </form>
            
            <hr />
      
            <h2 id="company">Company API:</h2>
            
            <h3 id="companySpecific">Specific Company:</h3>

            <p>All about LinkedIn via the Company API:</p>
            
            <?php
            $OBJ_linkedin->setResponseFormat(LINKEDIN::_RESPONSE_XML);
            $response = $OBJ_linkedin->company('1337:(id,name,ticker,description,logo-url,locations:(address,is-headquarters))');
            if($response['success'] === TRUE) {
              $company = new SimpleXMLElement($response['linkedin']);
              ?>
              <div style=""><span style="font-weight: bold;"><?php echo $company->name;?> (<?php echo $company->ticker;?>)</span>&nbsp;<img src="<?php echo $company->{'logo-url'};?>" alt="<?php echo $company->name;?>" title="<?php echo $company->name;?>" style="vertical-align: middle;" /></div>
              <div style="margin: 0.5em 0 1em 2em;">
                <?php
                foreach($company->locations->location as $location) {
                  if($location->{'is-headquarters'} == 'true') {
                    $address = $location->address;
                    ?>
                    Headquarters: <?php echo $address->street1;?>, <?php echo $address->city;?>
                    <?php
                  }
                }
                ?>
              </div>
              <div style="margin: 0.5em 0 1em 2em;">
                Description: <?php echo $company->description;?>
              </div>
              <?php
              $response = $OBJ_linkedin->companyProducts('1337', ':(id,name,type,recommendations:(recommender,id))');
              if($response['success'] === TRUE) {
                $response['linkedin'] = new SimpleXMLElement($response['linkedin']);
                ?>
                <div style="margin: 0.5em 0 1em 2em;">
                  Products: <pre><?php print_r($response['linkedin']);?></pre>
                </div>
                <?php
              } else {
                // company product retrieval failed
                echo "Error retrieving company products:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response) . "</pre>";
              }
            } else {
              // people search retrieval failed
              echo "Error retrieving company information:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response) . "</pre>";
            }
            ?>
            
            <hr />
            
            <h3 id="companyFollowed">Followed Companies:</h3>

            <p>Companies that you are currently following:</p>
            
            <?php
            $OBJ_linkedin->setResponseFormat(LINKEDIN::_RESPONSE_XML);
            $response = $OBJ_linkedin->followedCompanies();
            if($response['success'] === TRUE) {
              $followed = new SimpleXMLElement($response['linkedin']);
              if((int)$followed['total'] > 0) {
                foreach($followed->company as $company) {
                  $cid  = $company->id;
                  $name = $company->name;
                  ?>
                  <div style=""><span style="font-weight: bold;"><?php echo $name;?></span></div>
                  <div style="margin: 0.5em 0 1em 2em;">
                    <a href="<?php echo $_SERVER['PHP_SELF'];?>?<?php echo LINKEDIN::_GET_TYPE;?>=unfollowCompany&amp;nCompanyId=<?php echo $cid;?>">Unfollow</a>
                  </div>
                  <?php
                }
              } else {
                // no companies follows
                echo '<div>You do not currently follow any companies.</div>';
              }
            } else {
              // people search retrieval failed
              echo "Error retrieving followed companies:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response) . "</pre>";
            }
            ?>
            
            <hr />
            
            <h3 id="companySuggested">Suggested Companies:</h3>

            <p>Companies that LinkedIn suggests that you follow:</p>
            
            <?php
            $OBJ_linkedin->setResponseFormat(LINKEDIN::_RESPONSE_XML);
            $response = $OBJ_linkedin->suggestedCompanies();
            if($response['success'] === TRUE) {
              $suggested = new SimpleXMLElement($response['linkedin']);
              if((int)$suggested['count'] > 0) {
                foreach($suggested->company as $company) {
                  $cid  = $company->id;
                  $name = $company->name;
                  ?>
                  <div style=""><span style="font-weight: bold;"><?php echo $name;?></span></div>
                  <div style="margin: 0.5em 0 1em 2em;">
                    <?php
                    echo '<a href="' . $_SERVER['PHP_SELF'] . '?' . LINKEDIN::_GET_TYPE . '=followCompany&amp;nCompanyId=' . $cid . '">Follow</a>';
                    ?>
                  </div>
                  <?php
                }
              } else {
                // no suggested follows
                echo '<div>LinkedIn is not suggesting any companies for you to follow at this time.</div>';
              }
            } else {
              // people search retrieval failed
              echo "Error retrieving suggested companies:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response) . "</pre>";
            }
            ?>
            
            <hr />
            
            <h3 id="companySearch">Company Search:</h3>
            
            <?php
            $OBJ_linkedin->setResponseFormat(LINKEDIN::_RESPONSE_JSON);
            $keywords = (isset ($_GET['keywords'])) ? $_GET['keywords'] : "Microsoft";
      			?>
      			<form action="<?php echo $_SERVER['PHP_SELF'];?>#companySearch" method="get">
      				Search by Keywords: <input type="text" value="<?php echo $keywords;?>" name="keywords" /><input type="submit" value="Search" />
      			</form>
      			<?php
			      $query    = '?sort=company-size&keywords=' . $keywords;
            $response = $OBJ_linkedin->searchCompanies($query); 
            if($response['success'] === TRUE) {
              echo "<pre>" . print_r($response['linkedin'], TRUE) . "</pre>";
            } else {
              // company search retrieval failed
              echo "Error retrieving company search results:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response) . "</pre>";                
            }
          } else {
            // user isn't connected
            ?>
            <form id="linkedin_connect_form" action="<?php echo $_SERVER['PHP_SELF'];?>" method="get">
              <input type="hidden" name="<?php echo LINKEDIN::_GET_TYPE;?>" id="<?php echo LINKEDIN::_GET_TYPE;?>" value="initiate" />
              <input type="submit" value="Connect to LinkedIn" />
            </form>
            <?php
          }
          ?>
        </body>
      </html>
      <?php
      break;
  }
} catch(LinkedInException $e) {
  // exception raised by library call
  echo $e->getMessage();
}

?>