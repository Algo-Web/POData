<?php
/** 
 * Implementation of IDataServiceMetadataProvider.
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
use ODataProducer\Providers\Metadata\ResourceStreamInfo;
use ODataProducer\Providers\Metadata\ResourceAssociationSetEnd;
use ODataProducer\Providers\Metadata\ResourceAssociationSet;
use ODataProducer\Common\NotImplementedException;
use ODataProducer\Providers\Metadata\Type\EdmPrimitiveType;
use ODataProducer\Providers\Metadata\ResourceSet;
use ODataProducer\Providers\Metadata\ResourcePropertyKind;
use ODataProducer\Providers\Metadata\ResourceProperty;
use ODataProducer\Providers\Metadata\ResourceTypeKind;
use ODataProducer\Providers\Metadata\ResourceType;
use ODataProducer\Common\InvalidOperationException;
use ODataProducer\Providers\Metadata\IDataServiceMetadataProvider;
require_once 'ODataProducer\Providers\Metadata\IDataServiceMetadataProvider.php';
use ODataProducer\Providers\Metadata\ServiceBaseMetadata;

//Begin Resource Classes

/**
 * Post entity type.
 * 
 * @category  Service
 * @package   WordPress
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class Post
{
    //Key Edm.Int32
    public $PostID;
    //Edm.Int32
    public $Author;
    //Edm.DateTime
    public $Date;
    //Edm.DateTime
    public $DateGmt;
    //Edm.String
    public $Content;
    //Edm.String
    public $Title;
    //Edm.String
    public $Excerpt;
    //Edm.String
    public $Status;
    //Edm.String
    public $CommentStatus;
    //Edm.String
    public $PingStatus;
    //Edm.String
    public $Password;
    //Edm.String
    public $Name;
    //Edm.String
    public $ToPing;
    //Edm.String
    public $Pinged;
    //Edm.DateTime
    public $Modified;
    //Edm.DateTime
    public $ModifiedGmt;
    //Edm.String
    public $ContentFiltered;
    //Edm.Int32
    public $ParentID;
    //Edm.String
    public $Guid;
    //Edm.Int32
    public $MenuOrder;
    //Edm.String
    public $Type;
    //Edm.String
    public $MimeType;
    //Edm.Int32
    public $CommentCount;
    //Navigation Property User (ResourceReference)
    public $User;
    //Navigation Property tags (ResourceSetReference)
    public $Tags;
    //Navigation Property categories (ResourceSetReference)
    public $Categories;
    //Navigation Property comments (ResourceSetReference)
    public $Comments;
}

/**
 * Tag entity type.
 * 
 * @category  Service
 * @package   WordPress
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class Tag
{
    //Key Edm.Int32
    public $TagID;
    //Edm.String
    public $Name;
    //Edm.String
    public $Slug;
    //Edm.String
    public $Description;
    //Navigation Property Posts (ResourceSetReference)
    public $Posts;
}

/**
 * Category entity type.
 * 
 * @category  Service
 * @package   WordPress
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class Category
{
    //Key Edm.Int32
    public $CategoryID;
    //Edm.String
    public $Name;
    //Edm.String
    public $Slug;
    //Edm.String
    public $Description;
    //Navigation Property Posts (ResourceSetReference)
    public $Posts;
}

/**
 * Comment entity type.
 * 
 * @category  Service
 * @package   WordPress
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class Comment
{
    //Key Edm.Int32
    public $CommentID;
    //Edm.Int32
    public $PostID;
    //Edm.String
    public $Author;
    //Edm.String
    public $AuthorEmail;
    //Edm.String
    public $AuthorUrl;
    //Edm.String
    public $AuthorIp;
    //Edm.DateTime
    public $Date;
    //Edm.DateTime
    public $DateGmt;
    //Edm.String
    public $Content;
    //Edm.Int32
    public $Karma;
    //Edm.String
    public $Approved;
    //Edm.String
    public $Agent;
    //Edm.String
    public $Type;
    //Edm.Int32
    public $ParentID;
    //Edm.Int32
    public $UserID;
    //Navigation Property User (ResourceReference)
    public $User;
    //Navigation Property Post (ResourceReference)
    public $Post;
}

/**
 * User entity type.
 * 
 * @category  Service
 * @package   WordPress
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class User
{
    //Key Edm.Int32
    public $UserID;
    //Edm.String
    public $Login;
    //Edm.String
    public $Nicename;
    //Edm.String
    public $Email;
    //Edm.String
    public $Url;
    //Edm.DateTime
    public $Registered;
    //Edm.Int16
    public $Status;
    //Edm.String
    public $DisplayName;
    //Navigation Property Posts (ResourceSetReference)
    public $Posts;
    //Navigation Property Comments (ResourceSetReference)
    public $Comments;
}

//End Resource Classes


/**
 * Create WordPress metadata.
 * 
 * @category  Service
 * @package   WordPress
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class CreateWordPressMetadata
{
    /**
     * create metadata
     * 
     * @throws InvalidOperationException
     * 
     * @return NorthWindMetadata
     */
    public static function create()
    {
        $metadata = new ServiceBaseMetadata('WordPressEntities', 'WordPress');
    
        //Register the entity (resource) type 'Post'
        $postsEntityType = $metadata->addEntityType(new ReflectionClass('Post'), 'Post', 'WordPress');
        $metadata->addKeyProperty($postsEntityType, 'PostID', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($postsEntityType, 'Author', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($postsEntityType, 'Date', EdmPrimitiveType::DATETIME);
        $metadata->addPrimitiveProperty($postsEntityType, 'DateGmt', EdmPrimitiveType::DATETIME);
        $metadata->addPrimitiveProperty($postsEntityType, 'Content', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($postsEntityType, 'Title', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($postsEntityType, 'Excerpt', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($postsEntityType, 'Status', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($postsEntityType, 'CommentStatus', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($postsEntityType, 'PingStatus', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($postsEntityType, 'Password', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($postsEntityType, 'Name', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($postsEntityType, 'ToPing', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($postsEntityType, 'Pinged', EdmPrimitiveType::STRING);
        $metadata->addETagProperty($postsEntityType, 'Modified', EdmPrimitiveType::DATETIME);
        $metadata->addPrimitiveProperty($postsEntityType, 'ModifiedGmt', EdmPrimitiveType::DATETIME);
        $metadata->addPrimitiveProperty($postsEntityType, 'ContentFiltered', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($postsEntityType, 'ParentID', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($postsEntityType, 'Guid', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($postsEntityType, 'MenuOrder', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($postsEntityType, 'Type', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($postsEntityType, 'MimeType', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($postsEntityType, 'CommentCount', EdmPrimitiveType::INT32);
    
        //Register the entity (resource) type 'Tag'
        $tagsEntityType = $metadata->addEntityType(new ReflectionClass('Tag'), 'Tag', 'WordPress');
        $metadata->addKeyProperty($tagsEntityType, 'TagID', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($tagsEntityType, 'Name', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($tagsEntityType, 'Slug', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($tagsEntityType, 'Description', EdmPrimitiveType::STRING);
    
        //Register the entity (resource) type 'Category'
        $catsEntityType = $metadata->addEntityType(new ReflectionClass('Category'), 'Category', 'WordPress');
        $metadata->addKeyProperty($catsEntityType, 'CategoryID', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($catsEntityType, 'Name', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($catsEntityType, 'Slug', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($catsEntityType, 'Description', EdmPrimitiveType::STRING);
    
        //Register the entity (resource) type 'Comment'
        $commentsEntityType = $metadata->addEntityType(new ReflectionClass('Comment'), 'Comment', 'WordPress');
        $metadata->addKeyProperty($commentsEntityType, 'CommentID', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($commentsEntityType, 'PostID', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($commentsEntityType, 'Author', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($commentsEntityType, 'AuthorEmail', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($commentsEntityType, 'AuthorUrl', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($commentsEntityType, 'AuthorIp', EdmPrimitiveType::STRING);
        $metadata->addETagProperty($commentsEntityType, 'Date', EdmPrimitiveType::DATETIME);
        $metadata->addPrimitiveProperty($commentsEntityType, 'DateGmt', EdmPrimitiveType::DATETIME);
        $metadata->addPrimitiveProperty($commentsEntityType, 'Content', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($commentsEntityType, 'Karma', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($commentsEntityType, 'Approved', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($commentsEntityType, 'Agent', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($commentsEntityType, 'Type', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($commentsEntityType, 'ParentID', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($commentsEntityType, 'UserID', EdmPrimitiveType::INT32);
    
        //Register the entity (resource) type 'User'
        $usersEntityType = $metadata->addEntityType(new ReflectionClass('User'), 'User', 'WordPress');
        $metadata->addKeyProperty($usersEntityType, 'UserID', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($usersEntityType, 'Login', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($usersEntityType, 'Nicename', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($usersEntityType, 'Email', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($usersEntityType, 'Url', EdmPrimitiveType::STRING);
        $metadata->addETagProperty($usersEntityType, 'Registered', EdmPrimitiveType::DATETIME);
        $metadata->addPrimitiveProperty($usersEntityType, 'Status', EdmPrimitiveType::INT16);
        $metadata->addPrimitiveProperty($usersEntityType, 'DisplayName', EdmPrimitiveType::STRING);
    
        $postsResourceSet = $metadata->addResourceSet('Posts', $postsEntityType);
        $tagsResourceSet = $metadata->addResourceSet('Tags', $tagsEntityType);
        $catsResourceSet = $metadata->addResourceSet('Categories', $catsEntityType);
        $commentsResourceSet = $metadata->addResourceSet('Comments', $commentsEntityType);
        $usersResourceSet = $metadata->addResourceSet('Users', $usersEntityType);
        //associations of Post
        $metadata->addResourceReferenceProperty($postsEntityType, 'User', $usersResourceSet);
        $metadata->addResourceSetReferenceProperty($postsEntityType, 'Tags', $tagsResourceSet);
        $metadata->addResourceSetReferenceProperty($postsEntityType, 'Categories', $catsResourceSet);
        $metadata->addResourceSetReferenceProperty($postsEntityType, 'Comments', $commentsResourceSet);
        //associations of Tag
        $metadata->addResourceSetReferenceProperty($tagsEntityType, 'Posts', $postsResourceSet);
        //associations of Category
        $metadata->addResourceSetReferenceProperty($catsEntityType, 'Posts', $postsResourceSet);
        //associations of Comment
        $metadata->addResourceReferenceProperty($commentsEntityType, 'User', $usersResourceSet);
        $metadata->addResourceReferenceProperty($commentsEntityType, 'Post', $postsResourceSet);
        //associations of User
        $metadata->addResourceSetReferenceProperty($usersEntityType, 'Posts', $postsResourceSet);
        $metadata->addResourceSetReferenceProperty($usersEntityType, 'Comments', $commentsResourceSet);
    
        return $metadata;
    }
}


?>