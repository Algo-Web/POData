<?php
/** 
 * Implementation of IDataServiceQueryProvider.
 * 
 * PHP version 5.3
 * 
 * @category  Service
 * @package   WordPress
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *  Redistributions of source code must retain the above copyright notice, this list
 *  of conditions and the following disclaimer.
 *  Redistributions in binary form must reproduce the above copyright notice, this
 *  list of conditions  and the following disclaimer in the documentation and/or
 *  other materials provided with the distribution.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A  PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 */
use ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use ODataProducer\Providers\Metadata\ResourceSet;
use ODataProducer\Providers\Metadata\ResourceProperty;
use ODataProducer\Providers\Query\IDataServiceQueryProvider;
require_once "WordPressMetadata.php";
require_once "ODataProducer\Providers\Query\IDataServiceQueryProvider.php";
/** The name of the database for WordPress */
define('DB_NAME', 'wordpress');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'root');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/**
 * WordPressQueryProvider implemetation of IDataServiceQueryProvider.
 * 
 * @category  Service
 * @package   WordPress
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class WordPressQueryProvider implements IDataServiceQueryProvider
{
    /**
     * Handle to connection to Database     
     */
    private $_connectionHandle = null;

    /**
     * Constructs a new instance of WordPressQueryProvider
     * 
     */
    public function __construct()
    {
        $this->_connectionHandle = @mysql_connect(DB_HOST, DB_USER, DB_PASSWORD, true);
        if ( $this->_connectionHandle ) {
        } else {             
             die(print_r(mysql_error(), true));
        } 

        mysql_select_db(DB_NAME, $this->_connectionHandle);
    }
    
    /**
     * Gets collection of entities belongs to an entity set
     * 
     * @param ResourceSet $resourceSet The entity set whose 
     * entities needs to be fetched
     * 
     * @return array(Object)
     */
    public function getResourceSet(ResourceSet $resourceSet)
    {   
        $resourceSetName =  $resourceSet->getName();
        if ($resourceSetName !== 'Posts' 
            && $resourceSetName !== 'Tags' 
            && $resourceSetName !== 'Categories' 
            && $resourceSetName !== 'Comments' 
            && $resourceSetName !== 'Users'
        ) {
            die('(WordPressQueryProvider) Unknown resource set ' . $resourceSetName);
        }

       
        $returnResult = array();
        switch ($resourceSetName) {
        case 'Posts':
            $query = "SELECT * FROM `wp_posts` WHERE"
                   ." wp_posts.post_type = 'post'"
                   ." AND wp_posts.post_status = 'publish'";
            $stmt = mysql_query($query);
            $returnResult = $this->_serializePosts($stmt);
            break;
        case 'Tags':
            $query = "SELECT t.*, tt.description"
                   ." FROM `wp_terms` AS t INNER JOIN `wp_term_taxonomy` as tt"
                   ." ON tt.term_id = t.term_id"
                   ." WHERE tt.taxonomy = 'post_tag'";
            $stmt = mysql_query($query);
            $returnResult = $this->_serializeTags($stmt);
            break;
        case 'Categories':
            $query = "SELECT t.*, tt.description"
                   ." FROM `wp_terms` AS t INNER JOIN `wp_term_taxonomy` as tt"
                   ." ON tt.term_id = t.term_id"
                   ." WHERE tt.taxonomy = 'category'";
            $stmt = mysql_query($query);
            $returnResult = $this->_serializeCategories($stmt);
            break;
        case 'Comments':
            $query = "SELECT * FROM `wp_comments` WHERE"
                   ." wp_comments.comment_approved = 1";
            $stmt = mysql_query($query);
            $returnResult = $this->_serializeComments($stmt);
            break;
        case 'Users':
            $query = "SELECT * FROM `wp_users`";
            $stmt = mysql_query($query);
            $returnResult = $this->_serializeUsers($stmt);
            break;
        }
        
        mysql_free_result($stmt);
        return $returnResult;
    }
    
    /**
     * Gets an entity instance from an entity set identifed by a key
     * 
     * @param ResourceSet   $resourceSet   The entity set from which an entity 
     *                                     needs to be fetched
     * @param KeyDescriptor $keyDescriptor The key to identify the entity 
     *                                     to be fetched
     * 
     * @return Object/NULL Returns entity instance if found else null
     */
    public function getResourceFromResourceSet(ResourceSet $resourceSet, KeyDescriptor $keyDescriptor)
    {   
        $resourceSetName =  $resourceSet->getName();
        if ($resourceSetName !== 'Posts' 
            && $resourceSetName !== 'Tags' 
            && $resourceSetName !== 'Categories' 
            && $resourceSetName !== 'Comments' 
            && $resourceSetName !== 'Users'
        ) {
            die('(WordPressQueryProvider) Unknown resource set ' . $resourceSetName);
        }

        $namedKeyValues = $keyDescriptor->getValidatedNamedValues();
        $keys = array();
        foreach ($namedKeyValues as $key => $value) {
            $keys[] = "$key = '$value[0]' ";
        }
        $conditionStr = implode(' AND ', $keys);
        
        switch ($resourceSetName) {
        case 'Posts':
            $query = "SELECT * FROM `wp_posts` WHERE"
                   ." wp_posts.post_type = 'post'"
                   ." AND wp_posts.post_status = 'publish'"
                   ." AND wp_posts.ID = ".$namedKeyValues['PostID'][0];
            $stmt = mysql_query($query);
              
            //If resource not found return null to the library
            if (!mysql_num_rows($stmt)) {
                return null;
            } 
              
            $data = mysql_fetch_assoc($stmt);
            $result = $this->_serializePost($data);
            break;
        case 'Tags':
            $query = "SELECT t.*, tt.description"
                   ." FROM `wp_terms` AS t INNER JOIN `wp_term_taxonomy` as tt"
                   ." ON tt.term_id = t.term_id"
                   ." WHERE tt.taxonomy = 'post_tag'"
                   ." AND t.term_id = ".$namedKeyValues['TagID'][0];
            $stmt = mysql_query($query);
              
            //If resource not found return null to the library
            if (!mysql_num_rows($stmt)) {
                return null;
            }
              
            $data = mysql_fetch_assoc($stmt);
            $result = $this->_serializeTag($data);
            break;
        case 'Categories':
            $query = "SELECT t.*, tt.description"
                   ." FROM `wp_terms` AS t INNER JOIN `wp_term_taxonomy` as tt"
                   ." ON tt.term_id = t.term_id"
                   ." WHERE tt.taxonomy = 'category'"
                   ." AND t.term_id = ".$namedKeyValues['CategoryID'][0];
            $stmt = mysql_query($query);
              
            //If resource not found return null to the library
            if (!mysql_num_rows($stmt)) {
                return null;
            }
              
            $data = mysql_fetch_assoc($stmt);
            $result = $this->_serializeCategory($data);
            break;
        case 'Comments':
            $query = "SELECT * FROM `wp_comments`"
                   ." WHERE comment_approved = 1" 
                   ." AND comment_ID = ".$namedKeyValues['CommentID'][0];
            $stmt = mysql_query($query);
              
            //If resource not found return null to the library
            if (!mysql_num_rows($stmt)) {
                return null;
            }
              
            $data = mysql_fetch_assoc($stmt);
            $result = $this->_serializeComment($data);
            break;
        case 'Users':
            $query = "SELECT * FROM `wp_users` WHERE ID = ".$namedKeyValues['UserID'][0];
            $stmt = mysql_query($query);
              
            //If resource not found return null to the library
            if (!mysql_num_rows($stmt)) {
                return null;
            }
              
            $data = mysql_fetch_assoc($stmt);
            $result = $this->_serializeUser($data);
            break;
        }
        
        mysql_free_result($stmt);
        return $result;
    }
    
    /**
     * Get related resource set for a resource
     * 
     * @param ResourceSet      $sourceResourceSet    The source resource set
     * @param mixed            $sourceEntityInstance The resource
     * @param ResourceSet      $targetResourceSet    The resource set of 
     *                                               the navigation property
     * @param ResourceProperty $targetProperty       The navigation property to be 
     *                                               retrieved
     *                                               
     * @return array(Objects)/array() Array of related resource if exists, if no 
     *                                related resources found returns empty array
     */
    public function  getRelatedResourceSet(ResourceSet $sourceResourceSet, 
        $sourceEntityInstance, 
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty
    ) {    
        $result = array();
        $srcClass = get_class($sourceEntityInstance);
        $navigationPropName = $targetProperty->getName();
        
        switch (true) {
        case ($srcClass == 'Post'):
            if ($navigationPropName == 'Tags') {
                $query = "SELECT t.*, tt.description"
                       ." FROM wp_terms AS t"
                       ." INNER JOIN wp_term_taxonomy AS tt"
                       ." ON tt.term_id = t.term_id"
                       ." INNER JOIN wp_term_relationships AS tr"
                       ." ON tr.term_taxonomy_id = tt.term_taxonomy_id"
                       ." WHERE tt.taxonomy IN ('post_tag')"
                       ." AND tr.object_id IN ($sourceEntityInstance->PostID)";
                $stmt = mysql_query($query);
                if ( $stmt === false) {
                    die(mysql_error());
                }
                        
                $result = $this->_serializeTags($stmt);
            } elseif ($navigationPropName == 'Categories') {
                $query = "SELECT t.*, tt.description"
                       ." FROM wp_terms AS t"
                       ." INNER JOIN wp_term_taxonomy AS tt"
                       ." ON tt.term_id = t.term_id"
                       ." INNER JOIN wp_term_relationships AS tr"
                       ." ON tr.term_taxonomy_id = tt.term_taxonomy_id"
                       ." WHERE tt.taxonomy IN ('category')"
                       ." AND tr.object_id IN ($sourceEntityInstance->PostID)";
                $stmt = mysql_query($query);
                if ( $stmt === false) {            
                       die(mysql_error());
                }
                        
                $result = $this->_serializeCategories($stmt);
            } else if ($navigationPropName == 'Comments') {
                $query = "SELECT * FROM `wp_comments`"
                       ." WHERE comment_approved = 1" 
                       ." AND comment_post_ID = $sourceEntityInstance->PostID";
                $stmt = mysql_query($query);
                if ( $stmt === false) {
                    die(mysql_error());
                }
                        
                $result = $this->_serializeComments($stmt);
            } else {
                die('Post does not have navigation porperty with name: ' . $navigationPropName);
            }
            break;

        case ($srcClass == 'Tag'):
            if ($navigationPropName == 'Posts') {
                $query = "SELECT p . *" 
                         ." FROM wp_posts AS p"
                         ." INNER JOIN wp_term_relationships AS tr"
                         ." ON p.ID = tr.object_id"
                         ." INNER JOIN wp_term_taxonomy AS tt"
                         ." ON tr.term_taxonomy_id = tt.term_taxonomy_id"
                         ." WHERE tt.term_id = $sourceEntityInstance->TagID"
                         ." AND p.post_type = 'post'"
                         ." AND p.post_status = 'publish'";
                $stmt = mysql_query($query);
                if ( $stmt === false) {
                            die(mysql_error());
                }
                        
                      $result = $this->_serializePosts($stmt);
            } else {
                die('Tag does not have navigation porperty with name: ' . $navigationPropName);
            }
            break;
                    
        case ($srcClass == 'Category'):
            if ($navigationPropName == 'Posts') {
                $query = "SELECT p . *" 
                         ." FROM wp_posts AS p"
                         ." INNER JOIN wp_term_relationships AS tr"
                         ." ON p.ID = tr.object_id"
                         ." INNER JOIN wp_term_taxonomy AS tt"
                         ." ON tr.term_taxonomy_id = tt.term_taxonomy_id"
                         ." WHERE tt.term_id = $sourceEntityInstance->CategoryID"
                         ." AND p.post_type = 'post'"
                         ." AND p.post_status = 'publish'";
                $stmt = mysql_query($query);
                if ( $stmt === false) {
                    die(mysql_error());
                }
                        
                $result = $this->_serializePosts($stmt);
            } else {
                die('Category does not have navigation porperty with name: ' . $navigationPropName);
            }
            break;
                 
        case ($srcClass == 'Comment'):
            die('Comment does not have navigation porperty with name: ' . $navigationPropName);
            break;
                    
        case ($srcClass == 'User'):
            if ($navigationPropName == 'Posts') {
                $query = "SELECT * FROM `wp_posts` WHERE"
                       ." wp_posts.post_type = 'post'"
                       ." AND wp_posts.post_status = 'publish'"
                       ." AND wp_posts.post_author = $sourceEntityInstance->UserID";
                $stmt = mysql_query($query);
                if ( $stmt === false) {
                    die(mysql_error());
                }
                            
                $result = $this->_serializePosts($stmt);
            } elseif ($navigationPropName == 'Comments') {
                $query = "SELECT * FROM `wp_comments`"
                     ." WHERE comment_approved = 1" 
                     ." AND wp_comments.user_id = $sourceEntityInstance->UserID";
                $stmt = mysql_query($query);
                if ( $stmt === false) {            
                    die(mysql_error());
                }
                        
                $result = $this->_serializeComments($stmt);
            } else {
                die('User does not have navigation porperty with name: ' . $navigationPropName);
            }
            break;
        }
        
        mysql_free_result($stmt);
        return $result;
    }
    
    /**
     * Gets a related entity instance from an entity set identifed by a key
     * 
     * @param ResourceSet      $sourceResourceSet    The entity set related to
     *                                               the entity to be fetched.
     * @param object           $sourceEntityInstance The related entity instance.
     * @param ResourceSet      $targetResourceSet    The entity set from which
     *                                               entity needs to be fetched.
     * @param ResourceProperty $targetProperty       The metadata of the target 
     *                                               property.
     * @param KeyDescriptor    $keyDescriptor        The key to identify the entity 
     *                                               to be fetched.
     * 
     * @return Object/NULL Returns entity instance if found else null
     */
    public function  getResourceFromRelatedResourceSet(ResourceSet $sourceResourceSet, 
        $sourceEntityInstance, 
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        KeyDescriptor $keyDescriptor
    ) {
        $result = array();
        $srcClass = get_class($sourceEntityInstance);
        $navigationPropName = $targetProperty->getName();
        
        $keys = array();
        $namedKeyValues = $keyDescriptor->getValidatedNamedValues();
        foreach ($namedKeyValues as $key => $value) {
            $keys[] = "$key = '$value[0]' ";
        }
        $conditionStr = implode(' AND ', $keys);
        
        switch (true) {
        case ($srcClass == 'Post'):
            if ($navigationPropName == 'Tags') {
                $query = "SELECT t.*, tt.description"
                       ." FROM wp_terms AS t"
                       ." INNER JOIN wp_term_taxonomy AS tt"
                       ." ON tt.term_id = t.term_id"
                       ." INNER JOIN wp_term_relationships AS tr"
                       ." ON tr.term_taxonomy_id = tt.term_taxonomy_id"
                       ." WHERE tt.taxonomy IN ('post_tag')"
                       ." AND tr.object_id IN ($sourceEntityInstance->PostID)"
                       ." AND tt.term_id = ".$namedKeyValues['TagID'][0];
                $stmt = mysql_query($query);
                $result = $this->_serializeTags($stmt);
            } elseif ($navigationPropName == 'Categories') {
                $query = "SELECT t.*, tt.description"
                       ." FROM wp_terms AS t"
                       ." INNER JOIN wp_term_taxonomy AS tt"
                       ." ON tt.term_id = t.term_id"
                       ." INNER JOIN wp_term_relationships AS tr"
                       ." ON tr.term_taxonomy_id = tt.term_taxonomy_id"
                       ." WHERE tt.taxonomy IN ('category')"
                       ." AND tr.object_id IN ($sourceEntityInstance->PostID)"
                       ." AND tt.term_id = ".$namedKeyValues['CategoryID'][0];
                $stmt = mysql_query($query);
                $result = $this->_serializeCategories($stmt);
            } else if ($navigationPropName == 'Comments') {
                $query = "SELECT * FROM `wp_comments`"
                       ." WHERE comment_approved = 1" 
                       ." AND comment_post_ID = $sourceEntityInstance->PostID"
                       ." AND comment_ID = ".$namedKeyValues['CommentID'][0];
                $stmt = mysql_query($query);
                $result = $this->_serializeComments($stmt);
            } else {
                die('Post does not have navigation porperty with name: ' . $navigationPropName);
            }
            break;

        case ($srcClass == 'Tag'):
            if ($navigationPropName == 'Posts') {
                $query = "SELECT p . *" 
                         ." FROM wp_posts AS p"
                         ." INNER JOIN wp_term_relationships AS tr"
                         ." ON p.ID = tr.object_id"
                         ." INNER JOIN wp_term_taxonomy AS tt"
                         ." ON tr.term_taxonomy_id = tt.term_taxonomy_id"
                         ." WHERE tt.term_id = $sourceEntityInstance->TagID"
                         ." AND p.post_type = 'post'"
                         ." AND p.post_status = 'publish'"
                         ." AND p.ID = ".$namedKeyValues['PostID'][0];
                $stmt = mysql_query($query);
                $result = $this->_serializePosts($stmt);
            } else {
                die('Tag does not have navigation porperty with name: ' . $navigationPropName);
            }
            break;
                    
        case ($srcClass == 'Category'):
            if ($navigationPropName == 'Posts') {
                $query = "SELECT p . *" 
                         ." FROM wp_posts AS p"
                         ." INNER JOIN wp_term_relationships AS tr"
                         ." ON p.ID = tr.object_id"
                         ." INNER JOIN wp_term_taxonomy AS tt"
                         ." ON tr.term_taxonomy_id = tt.term_taxonomy_id"
                         ." WHERE tt.term_id = $sourceEntityInstance->CategoryID"
                         ." AND p.post_type = 'post'"
                         ." AND p.post_status = 'publish'"
                         ." AND p.ID = ".$namedKeyValues['PostID'][0];
                $stmt = mysql_query($query);
                $result = $this->_serializePosts($stmt);
            } else {
                die('Category does not have navigation porperty with name: ' . $navigationPropName);
            }
            break;
                 
        case ($srcClass == 'Comment'):
            die('Comment does not have navigation porperty with name: ' . $navigationPropName);
            break;
                    
        case ($srcClass == 'User'):
            if ($navigationPropName == 'Posts') {
                 $query = "SELECT * FROM `wp_posts` WHERE"
                        ." wp_posts.post_type = 'post'"
                        ." AND wp_posts.post_status = 'publish'"
                        ." AND wp_posts.post_author = $sourceEntityInstance->UserID"
                        ." AND wp_posts.ID = ".$namedKeyValues['PostID'][0];
                 $stmt = mysql_query($query);
                 $result = $this->_serializePosts($stmt);
            } elseif ($navigationPropName == 'Comments') {
                 $query = "SELECT * FROM `wp_comments`"
                      ." WHERE comment_approved = 1" 
                      ." AND wp_comments.user_id = $sourceEntityInstance->UserID"
                      ." AND wp_comments.comment_ID = ".$namedKeyValues['CommentID'][0];
                 $stmt = mysql_query($query);
                 $result = $this->_serializeComments($stmt);
            } else {
                 die('User does not have navigation porperty with name: ' . $navigationPropName);
            }
            break;
        }
        
        mysql_free_result($stmt);
        return empty($result) ? null : $result[0];
    }
    /**
     * Get related resource for a resource
     * 
     * @param ResourceSet      $sourceResourceSet    The source resource set
     * @param mixed            $sourceEntityInstance The source resource
     * @param ResourceSet      $targetResourceSet    The resource set of 
     *                                               the navigation property
     * @param ResourceProperty $targetProperty       The navigation property to be 
     *                                               retrieved
     * 
     * @return Object/null The related resource if exists else null
     */
    public function getRelatedResourceReference(ResourceSet $sourceResourceSet, 
        $sourceEntityInstance, 
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty
    ) {
        $result = null;
        $srcClass = get_class($sourceEntityInstance);
        $navigationPropName = $targetProperty->getName();
        
        switch (true) {
        case ($srcClass == 'Post'):
            if ($navigationPropName == 'User') {
                $query = "SELECT * FROM `wp_users` WHERE ID = $sourceEntityInstance->Author";
                $stmt = mysql_query($query);
                $stmt = mysql_query($query);
                $data = mysql_fetch_assoc($stmt);
                $result = $this->_serializeUser($data);
                if ( $stmt === false) {            
                    die(mysql_error());
                }
                        
                if (!mysql_num_rows($stmt)) {
                    $result =  null;
                }
            } else {
                die('Post does not have navigation porperty with name: ' . $navigationPropName);
            }
            break;

        case ($srcClass == 'Comment'):
            if ($navigationPropName == 'User') {
                $query = "SELECT * FROM `wp_users` WHERE ID = $sourceEntityInstance->UserID";
                $stmt = mysql_query($query);
                if ( $stmt === false) {
                    die(mysql_error());
                }
                        
                if (!mysql_num_rows($stmt)) {
                    $result =  null;
                }
                        
                $data = mysql_fetch_assoc($stmt);
                $result = $this->_serializeUser($data);
                      
            } elseif ($navigationPropName == 'Post') {
                $query = "SELECT * FROM `wp_posts` WHERE"
                       ." wp_posts.post_type = 'post'"
                       ." AND wp_posts.post_status = 'publish'"
                       ." AND wp_posts.ID = $sourceEntityInstance->PostID";
                $stmt = mysql_query($query);
                if ( $stmt === false) {            
                    die(mysql_error());
                }
                        
                if (!mysql_num_rows($stmt)) {
                    $result =  null;
                }
                        
                $data = mysql_fetch_assoc($stmt);
                $result = $this->_serializePost($data);
            } else {
                die('Comment does not have navigation porperty with name: ' . $navigationPropName);
            }
            break;
        }
        
        mysql_free_result($stmt);
        return $result;
    }
    
    /**
     * Serialize the mysql result array into Post objects
     * 
     * @param array(array) $result result of the mysql query
     * 
     * @return array(Object)
     */
    private function _serializePosts($result)
    {
        $posts = array();
        while ($record = mysql_fetch_array($result, MYSQL_ASSOC)) {
             $posts[] = $this->_serializePost($record);
        }

        return $posts;
    }

    /**
     * Serialize the mysql row into Post object
     * 
     * @param array $record each post row
     * 
     * @return Object
     */
    private function _serializePost($record)
    {
        $post = new Post();
        $post->PostID = $record['ID'];
        $post->Author = $record['post_author'];
        
        if (!is_null($record['post_date'])) {
            $dateTime = new DateTime($record['post_date']);
            $post->Date = $dateTime->format('Y-m-d\TH:i:s');
        } else {
            $post->Date = null;
        }
        
        if (!is_null($record['post_date_gmt'])) {
            $dateTime = new DateTime($record['post_date_gmt']);
            $post->DateGmt = $dateTime->format('Y-m-d\TH:i:s');
        } else {
            $post->DateGmt = null;
        }
        
        $post->Content = $record['post_content'];
        $post->Title = $record['post_title'];
        $post->Excerpt = $record['post_excerpt'];
        $post->Status = $record['post_status'];
        $post->CommentStatus = $record['comment_status'];
        $post->PingStatus = $record['ping_status'];
        $post->Password = $record['post_password'];
        $post->Name = $record['post_name'];
        $post->ToPing = $record['to_ping'];
        $post->Pinged = $record['pinged'];
        
        if (!is_null($record['post_modified'])) {
            $dateTime = new DateTime($record['post_modified']);
            $post->Modified = $dateTime->format('Y-m-d\TH:i:s');
        } else {
            $post->Modified = null;
        }
        
        if (!is_null($record['post_modified_gmt'])) {
            $dateTime = new DateTime($record['post_modified_gmt']);
            $post->ModifiedGmt = $dateTime->format('Y-m-d\TH:i:s');
        } else {
            $post->ModifiedGmt = null;
        }
        
        $post->ContentFiltered = $record['post_content_filtered'];
        $post->ParentID = $record['post_parent'];
        $post->Guid = $record['guid'];
        $post->MenuOrder = $record['menu_order'];
        $post->Type = $record['post_type'];
        $post->MimeType = $record['post_mime_type'];
        $post->CommentCount = $record['comment_count'];
        return $post;
    }
    
    /**
     * Serialize the mysql result array into Tag objects
     * 
     * @param array(array) $result result of the mysql query
     * 
     * @return array(Object)
     */
    private function _serializeTags($result)
    {
        $tags = array();
        while ($record = mysql_fetch_array($result, MYSQL_ASSOC)) {         
             $tags[] = $this->_serializeTag($record);
        }

        return $tags;
    }

    /**
     * Serialize the mysql row into Tag object
     * 
     * @param array $record each tag row
     * 
     * @return Object
     */
    private function _serializeTag($record)
    {
        $tag = new Tag();
        $tag->TagID = $record['term_id'];
        $tag->Name = $record['name'];
        $tag->Slug = $record['slug'];
        $tag->Description = $record['description'];
        return $tag;
    }
    
    /**
     * Serialize the mysql result array into Category objects
     * 
     * @param array(array) $result result of the mysql query
     * 
     * @return array(Object)
     */
    private function _serializeCategories($result)
    {
        $cats = array();
        while ($record = mysql_fetch_array($result, MYSQL_ASSOC)) {         
             $cats[] = $this->_serializeCategory($record);
        }

        return $cats;
    }

    /**
     * Serialize the mysql row into Category object
     * 
     * @param array $record each category row
     * 
     * @return Object
     */
    private function _serializeCategory($record)
    {
        $cat = new Category();
        $cat->CategoryID = $record['term_id'];
        $cat->Name = $record['name'];
        $cat->Slug = $record['slug'];
        $cat->Description = $record['description'];
        return $cat;
    }
    
    /**
     * Serialize the mysql result array into Comment objects
     * 
     * @param array(array) $result mysql query result
     * 
     * @return array(Object)
     */
    private function _serializeComments($result)
    {
        $comments = array();
        while ( $record = mysql_fetch_array($result, MYSQL_ASSOC)) {         
             $comments[] = $this->_serializeComment($record);
        }

        return $comments;
    }

    /**
     * Serialize the mysql row into Comment object
     * 
     * @param array $record each comment row
     * 
     * @return Object
     */
    private function _serializeComment($record)
    {
        $comment = new Comment();
        $comment->CommentID = $record['comment_ID'];
        $comment->PostID = $record['comment_post_ID'];
        $comment->Author = $record['comment_author'];
        $comment->AuthorEmail = $record['comment_author_email'];
        $comment->AuthorUrl = $record['comment_author_url'];
        $comment->AuthorIp = $record['comment_author_IP'];
        
        if (!is_null($record['comment_date'])) {
            $dateTime = new DateTime($record['comment_date']);
            $comment->Date = $dateTime->format('Y-m-d\TH:i:s');
        } else {
            $comment->Date = null;
        }
        
        if (!is_null($record['comment_date_gmt'])) {
            $dateTime = new DateTime($record['comment_date_gmt']);
            $comment->DateGmt = $dateTime->format('Y-m-d\TH:i:s');
        } else {
            $comment->DateGmt = null;
        }
        
        $comment->Content = $record['comment_content'];
        $comment->Karma = $record['comment_karma'];
        $comment->Approved = $record['comment_approved'];
        $comment->Agent = $record['comment_agent'];
        $comment->Type = $record['comment_type'];
        $comment->ParentID = $record['comment_parent'];
        $comment->UserID = $record['user_id'];
        return $comment;
    }
    
    /**
     * Serialize the mysql result array into User objects
     * 
     * @param array(array) $result result of the mysql query
     * 
     * @return array(Object)
     */
    private function _serializeUsers($result)
    {
        $users = array();
        while ($record = mysql_fetch_array($result, MYSQL_ASSOC)) {         
             $users[] = $this->_serializeUser($record);
        }

        return $users;
    }

    /**
     * Serialize the mysql row into User object
     * 
     * @param array $record each user row
     * 
     * @return Object
     */
    private function _serializeUser($record)
    {
        $user = new User();
        $user->UserID = $record['ID'];
        $user->Login = $record['user_login'];
        $user->Nicename = $record['user_nicename'];
        $user->Email = $record['user_email'];
        $user->Url = $record['user_url'];
        
        if (!is_null($record['user_registered'])) {
            $dateTime = new DateTime($record['user_registered']);
            $user->Registered = $dateTime->format('Y-m-d\TH:i:s');
        } else {
            $user->Registered = null;
        }
        
        $user->Status = $record['user_status'];
        $user->DisplayName = $record['display_name'];
        return $user;
    }
    
    
}
?>