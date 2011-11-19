<?php
class WithRelatedBehaviorTest extends CDbTestCase
{
    public $fixtures = array(
        'articles' => 'Article',
        'comments' => 'Comment',
        'groups' => 'Group',
        'tags' => 'Tag',
        'users' => 'User',
        'profiles' => 'Profile',
        'taggings' => 'Tagging',
    );

    private $article;
    private $comment1;
    private $comment2;
    private $tag1;
    private $tag2;
    private $user;
    private $group;
    private $profile;

    public function setUp()
    {
        $this->article = new Article;
        $this->article->title = 'Article';

        $this->comment1 = new Comment;
        $this->comment1->content = 'Comment 1';

        $this->comment2 = new Comment;
        $this->comment2->content = 'Comment 2';

        $this->tag1 = new Tag;
        $this->tag1->name = 'Tag 1';
        
        $this->tag2 = new Tag;
        $this->tag2->name = 'Tag 2';

        $this->user = new User;
        $this->user->name = 'User';

        $this->profile = new Profile;
        $this->profile->email = 'email@testing.com';

        $this->group = new Group;
        $this->group->name = 'Group';

        parent::setUp();
    }

    public function testValidationPassesWithEmptyRelations()
    {
        $this->article->comments = array();
        $this->article->tags = array();

        $validates = $this->article->withRelated->validate();
        
        $this->assertTrue($validates);
    }

    public function testEmptyHasRelationDoesNotBlockSave()
    {
        $this->article->comments = array();

        $saved = $this->article->withRelated->save(true, array('comments'));
        $this->assertTrue($saved, "Article was saved");
    }

    public function testEmptyBelongsToRelationDoesNotBlockSave()
    {
        $saved = $this->article->withRelated->save(true, array('user'));
        $this->assertTrue($saved);
    }

    public function testEmptyManyToManyRelationDoesNotBlockSave()
    {
        $this->article->tags = array();
        $saved = $this->article->withRelated->save(true, array('tags'));
        $this->assertTrue($saved);
    }

    public function testHasOneRelationIsSaved()
    {
        $this->user->profile = $this->profile;
        $saved = $this->user->withRelated->save(true, array('profile'));

        $this->assertTrue($saved);
        $this->assertNotNull($this->profile->id);
    }

    public function testHasManyRelationIsSaved()
    {

        $this->article->comments = array($this->comment1, $this->comment2);

        $saved = $this->article->withRelated->save(true, array('comments'));
        $this->assertTrue($saved);

        $this->assertTrue($this->article->id > 0);
        $this->assertTrue($this->comment1->id > 0);
        $this->assertTrue($this->comment2->id > 0);

        $this->assertEquals($this->article->id, $this->comment1->article_id);
        $this->assertEquals($this->article->id, $this->comment2->article_id);
    }

    public function testHasManyThoughRelationIsSaved()
    {
        $this->article->tags = array($this->tag1, $this->tag2);

        $saved = $this->article->withRelated->save(true, array('tags'));
        $this->assertTrue($saved);

        $taggedTimestamp = $this->article->taggings[0]->tagged;
        $this->assertNotNull($taggedTimestamp);
        sleep(2);
        $this->article->withRelated->save(true, array('tags'));

        $article = Article::model()->find();
        $this->assertEquals($taggedTimestamp, $article->taggings[0]->tagged);
    }

    public function testManyToManyRelationsAreNotDeletedUnnecessarily()
    {
        $this->article->tags = array($this->tag1, $this->tag2);
        $saved = $this->article->withRelated->save(true, array('tags'));

        $this->assertNotNull($this->article->id > 0);
        $this->assertTrue($this->tag1->id > 0);
        $this->assertTrue($this->tag2->id > 0);

        $fetchedArticle = Article::model()->findByPk($this->article->id);
        $this->assertEquals(2, count($fetchedArticle->tags));
        $this->assertEquals('Tag 1', $fetchedArticle->tags[0]->name);
        $this->assertEquals('Tag 2', $fetchedArticle->tags[1]->name);
    }

    public function testSavingFailsIfRelatedRecordsFailValidation()
    {
        $this->assertTrue($this->article->validate());

        $invalidComment = new Comment();
        $this->article->comments = array($invalidComment);

        $validateWithRelated = $this->article->withRelated->validate(array('comments'));
        $this->assertFalse($validateWithRelated);

        $saved = $this->article->withRelated->save(true, array('comments'));
        $this->assertFalse($saved);
    }

    public function testAppendingExistingRecordsInHasManyBehavesSanely()
    {
        $this->article->comments = array($this->comment1);
        $saved = $this->article->withRelated->save(true, array('comments'));
        $this->assertTrue($saved);

        $comment1Id = $this->comment1->id;

        $this->article->comments = array($this->comment1, $this->comment2);
        $saved = $this->article->withRelated->save(true, array('comments'));

        $this->assertTrue($saved);
        
        $this->assertEquals(2, count($this->article->comments));
        $this->assertEquals($comment1Id, $this->comment1->id);

        $this->assertTrue($this->comment2->validate());
        
        $this->assertNotNull($this->article->comments[1]);
    }

    public function testSave()
    {
        $this->user->group = $this->group;

        $this->article->user = $this->user;

        // Attach Comments
        $this->comment1->user = $this->user;
        $this->comment2->user = $this->user;
        $this->article->comments = array($this->comment1, $this->comment2);

        // Attach Tags
        $this->article->tags = array($this->tag1, $this->tag2);

        $result = $this->article->withRelated->save(true, array(
                                                         'user' => array('group'),
                                                         'comments' => array('user'),
                                                         'tags',
                                                    ));
        $this->assertTrue($result);
        
        $article = Article::model()->with(array(
                                               'user' => array(
                                                   'with' => 'group',
                                                   'alias' => 'article_user',
                                               ),
                                               'comments' => array(
                                                   'with' => array(
                                                       'user' => array(
                                                           'alias' => 'comment_user',
                                                       ),
                                                   ),
                                               ),
                                               'tags',
                                          ))->find();


        $this->assertNotNull($article);
        $this->assertEquals('Article', $article->title);
        $this->assertNotNull($article->user);
        $this->assertEquals('User', $article->user->name);
        $this->assertNotNull($article->user->group);
        $this->assertEquals('Group', $article->user->group->name);
        $this->assertEquals(2, count($article->comments));
        $this->assertEquals('Comment 1', $article->comments[0]->content);
        $this->assertEquals('Comment 2', $article->comments[1]->content);
        $this->assertNotNull($article->comments[0]->user);
        $this->assertEquals('User', $article->comments[0]->user->name);
        $this->assertNotNull($article->comments[1]->user);
        $this->assertEquals('User', $article->comments[1]->user->name);
        $this->assertEquals(2, count($article->tags));
        $this->assertEquals('Tag 1', $article->tags[0]->name);
        $this->assertEquals('Tag 2', $article->tags[1]->name);

        $article = Article::model()->with('comments')->find();

        $comments = $article->comments;
        $comments[0]->content = 'Comment 1 update';
        $comments[1]->content = 'Comment 2 update';

        $comment = new Comment;
        $comment->user = $this->user;
        $comment->content = 'Comment 3';

        $comments[] = $comment;

        $comment = new Comment;
        $comment->user = $this->user;
        $comment->content = 'Comment 4';

        $comments[] = $comment;

        $article->comments = $comments;

        $result = $article->withRelated->save(true, array('comments' => array('user')));
        
        $this->assertTrue($result);

        $article = Article::model()->with('comments')->find();
        $this->assertEquals(4, count($article->comments));
        $this->assertEquals('Comment 1 update', $article->comments[0]->content);
        $this->assertEquals('Comment 2 update', $article->comments[1]->content);
        $this->assertEquals('Comment 3', $article->comments[2]->content);
        $this->assertEquals('Comment 4', $article->comments[3]->content);

        $article = Article::model()->with('tags')->find();

        $tags = $article->tags;
        $tags[0]->name = 'Tag 1 update';
        $tags[1]->name = 'Tag 2 update';

        $tag = new Tag;
        $tag->name = 'Tag 3';

        $tags[] = $tag;

        $tag = new Tag;
        $tag->name = 'Tag 4';

        $tags[] = $tag;

        $article->tags = $tags;

        $result = $article->withRelated->save(true, array('tags'));
        $this->assertTrue($result);

        $article = Article::model()->with('tags')->find();
        $this->assertEquals(4, count($article->tags));
        $this->assertEquals('Tag 1 update', $article->tags[0]->name);
        $this->assertEquals('Tag 2 update', $article->tags[1]->name);
        $this->assertEquals('Tag 3', $article->tags[2]->name);
        $this->assertEquals('Tag 4', $article->tags[3]->name);
    }
}