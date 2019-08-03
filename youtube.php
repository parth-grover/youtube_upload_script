<?php
require_once __DIR__ . '/vendor/autoload.php';
class youtube_upload{
	
	private $auth_key;
	private $OAUTH2_CLIENT_ID = '592316429034-1hugk5lin1nh8165327c9vbq8eduvdri.apps.googleusercontent.com';
	private $OAUTH2_CLIENT_SECRET = 'upEoXEOQ_bHV-qcB64_cdtyG';
	
	function list_my_videos(){
		 $htmlBody = '';
		 try {
			$client = new Google_Client();
			$client->setClientId($this->OAUTH2_CLIENT_ID);
			$client->setClientSecret($this->OAUTH2_CLIENT_SECRET);
			$client->setScopes('https://www.googleapis.com/auth/youtube');
			$client->setAccessType('offline');
			$client->setAccessToken($this->auth_key);
			$client->setApprovalPrompt('force');
			 if ($client->getAccessToken()) {
				var_dump($client->getAccessToken());
				if($client->isAccessTokenExpired()) {
					$client->refreshToken($client->getRefreshToken());
					file_put_contents(api_get_path(INCLUDE_PATH).'extlib/youtube/token.txt', json_encode($client->getAccessToken()));
				}
				$youtube = new Google_Service_YouTube($client);
				$channelsResponse = $youtube->channels->listChannels('contentDetails', array(
				  'mine' => 'true',
				));

				
				foreach ($channelsResponse['items'] as $channel) {
				  $uploadsListId = $channel['contentDetails']['relatedPlaylists']['uploads'];

				  $playlistItemsResponse = $youtube->playlistItems->listPlaylistItems('snippet', array(
					'playlistId' => $uploadsListId,
					'maxResults' => 50
				  ));

				  $htmlBody .= "<h3>Videos in list $uploadsListId</h3><ul>";
				  foreach ($playlistItemsResponse['items'] as $playlistItem) {
					  $htmlBody .= '<li>'.$playlistItem['snippet']['resourceId']['videoId'].'</li>';
				  }
				  $htmlBody .= '</ul>';
				}
		  } else{
				$htmlBody =  'Problems creating the client';
			}
		} catch (Google_Service_Exception $e) {
			$htmlBody = sprintf('<p>A service error occurred: <code>%s</code></p>',
			htmlspecialchars($e->getMessage()));
		} catch (Google_Exception $e) {
			$htmlBody = sprintf('<p>An client error occurred: <code>%s</code></p>',
			htmlspecialchars($e->getMessage()));
		}
		return $htmlBody;
	}
	
	function get_video_detail($video_id){
		 $htmlBody = '';
		 try {
			$client = new Google_Client();
			$client->setClientId($this->OAUTH2_CLIENT_ID);
			$client->setClientSecret($this->OAUTH2_CLIENT_SECRET);
			$client->setScopes('https://www.googleapis.com/auth/youtube');
			$client->setAccessType('offline');
			$client->setAccessToken($this->auth_key);
			$client->setApprovalPrompt('force');
			 if ($client->getAccessToken()) {
				if($client->isAccessTokenExpired()) {
					$client->refreshToken($client->getRefreshToken());
					file_put_contents(api_get_path(INCLUDE_PATH).'extlib/youtube/token.txt', json_encode($client->getAccessToken()));
				}
				$youtube = new Google_Service_YouTube($client);
				$queryParams = [
					'id' => $video_id
				];

				$response = $youtube->videos->listVideos('snippet,contentDetails,statistics', $queryParams);
				$htmlBody = $response;
		  } else{
				echo 'Problems creating the client';
			}
		} catch (Google_Service_Exception $e) {
			$htmlBody = sprintf('<p>A service error occurred: <code>%s</code></p>',
			htmlspecialchars($e->getMessage()));
		} catch (Google_Exception $e) {
			$htmlBody = sprintf('<p>An client error occurred: <code>%s</code></p>',
			htmlspecialchars($e->getMessage()));
		}
		return $htmlBody;
	}
	
	function upload_video_to_youtube($video_path,$title,$description,$tags){
		 $htmlBody = '';
		 try{
			$client = new Google_Client();
			$client->setClientId($this->OAUTH2_CLIENT_ID);
			$client->setClientSecret($this->OAUTH2_CLIENT_SECRET);
			$client->setScopes('https://www.googleapis.com/auth/youtube');
			$client->setAccessType('offline');
			$client->setAccessToken($this->auth_key);
			$client->setApprovalPrompt('force');
			if ($client->getAccessToken()) {
				if($client->isAccessTokenExpired()) {
					$client->refreshToken($client->getRefreshToken());
					file_put_contents(api_get_path(INCLUDE_PATH).'extlib/youtube/token.txt', json_encode($client->getAccessToken()));
				}
				$youtube = new Google_Service_YouTube($client);
				$videoPath = $video_path;
				$snippet = new Google_Service_YouTube_VideoSnippet();
				$snippet->setTitle($title);
				$snippet->setDescription($description);
				$snippet->setTags($tags);
				$snippet->setCategoryId("22");
				$status = new Google_Service_YouTube_VideoStatus();
				$status->privacyStatus = "unlisted";
				$video = new Google_Service_YouTube_Video();
				$video->setSnippet($snippet);
				$video->setStatus($status);
				$chunkSizeBytes = 1 * 1024 * 1024;
				$client->setDefer(true);
				$insertRequest = $youtube->videos->insert("status,snippet", $video);
				$media = new Google_Http_MediaFileUpload(
					$client,
					$insertRequest,
					'video/*',
					null,
					true,
					$chunkSizeBytes
				);
				$media->setFileSize(filesize($videoPath));
				$status = false;
				$handle = fopen($videoPath, "rb");
				while (!$status && !feof($handle)) {
				  $chunk = fread($handle, $chunkSizeBytes);
				  $status = $media->nextChunk($chunk);
				}
				fclose($handle);
				$client->setDefer(false);
				$htmlBody = $status;
			} else{
				$htmlBody = 'Problems creating the client';
			}
		  } catch (Google_Service_Exception $e) {
			$htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
				htmlspecialchars($e->getMessage()));
		  } catch (Google_Exception $e) {
			$htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
				htmlspecialchars($e->getMessage()));
		  }
		return $htmlBody;
	}

	 function __construct() {
			$this->auth_key = file_get_contents(api_get_path(INCLUDE_PATH).'extlib/youtube/token.txt');
		}
} 
?>