<?php namespace Orchestra\Model\TestCase;

use Mockery as m;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Container\Container;
use Orchestra\Model\User;

class UserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Set mock connection
     */
    protected function addMockConnection($model)
    {
        $resolver = m::mock('Illuminate\Database\ConnectionResolverInterface');
        $model->setConnectionResolver($resolver);
        $resolver->shouldReceive('connection')
            ->andReturn(m::mock('Illuminate\Database\Connection'));
        $model->getConnection()
            ->shouldReceive('getQueryGrammar')
                ->andReturn(m::mock('Illuminate\Database\Query\Grammars\Grammar'));
        $model->getConnection()
            ->shouldReceive('getPostProcessor')
                ->andReturn(m::mock('Illuminate\Database\Query\Processors\Processor'));
    }

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(new Container);
    }

    /**
     * Teardown the test environment.
     */
    public function tearDown()
    {
        m::close();
    }

    /**
     * Test Orchestra\Model\User::roles() method.
     *
     * @test
     */
    public function testRolesMethod()
    {
        $model = new User;

        $this->addMockConnection($model);

        $stub = $model->roles();

        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Relations\BelongsToMany', $stub);
        $this->assertInstanceOf('\Orchestra\Model\Role', $stub->getQuery()->getModel());
    }

    /**
     * Test Orchestra\Model\User::attachRole() method.
     *
     * @test
     */
    public function testAttachRoleMethod()
    {
        $model = m::mock('\Orchestra\Model\User[roles]');
        $relationship = m::mock('\Illuminate\Database\Eloquent\Relations\BelongsToMany[sync]');

        $model->shouldReceive('roles')->once()->andReturn($relationship);
        $relationship->shouldReceive('sync')->once()->with(array(2), false)->andReturnNull();

        $model->attachRole(2);
    }

    /**
     * Test Orchestra\Model\User::detachRole() method.
     *
     * @test
     */
    public function testDetachRoleMethod()
    {
        $model = m::mock('\Orchestra\Model\User[roles]');
        $relationship = m::mock('\Illuminate\Database\Eloquent\Relations\BelongsToMany[detach]');

        $model->shouldReceive('roles')->once()->andReturn($relationship);
        $relationship->shouldReceive('detach')->once()->with(array(2))->andReturnNull();

        $model->detachRole(2);
    }

    /**
     * Test Orchestra\Model\User::is() method
     *
     * @test
     */
    public function testIsMethod()
    {
        $model = m::mock('\Orchestra\Model\User[roles]');
        $relationship = m::mock('\Illuminate\Database\Eloquent\Relations\BelongsToMany[lists]');

        $model->shouldReceive('roles')->times(4)->andReturn($relationship);
        $relationship->shouldReceive('lists')->times(4)->andReturn(array('admin', 'editor'));

        $this->assertTrue($model->is('admin'));
        $this->assertFalse($model->is('user'));

        $this->assertTrue($model->is(array('admin', 'editor')));
        $this->assertFalse($model->is(array('admin', 'user')));
    }

    /**
     * Test Orchestra\Support\Auth::is() method when invalid roles is
     * returned.
     *
     * @test
     */
    public function testIsMethodWhenInvalidRolesIsReturned()
    {
        $model = m::mock('\Orchestra\Model\User[roles]');
        $relationship = m::mock('\Illuminate\Database\Eloquent\Relations\BelongsToMany[lists]');

        $model->shouldReceive('roles')->times(4)->andReturn($relationship);
        $relationship->shouldReceive('lists')->times(4)->andReturn('foo');

        $this->assertFalse($model->is('admin'));
        $this->assertFalse($model->is('user'));

        $this->assertFalse($model->is(array('admin', 'editor')));
        $this->assertFalse($model->is(array('admin', 'user')));
    }

    /**
     * Test Orchestra\Model\User::isNot() method
     *
     * @test
     */
    public function testIsNotMethod()
    {
        $model = m::mock('\Orchestra\Model\User[roles]');
        $relationship = m::mock('\Illuminate\Database\Eloquent\Relations\BelongsToMany[lists]');

        $model->shouldReceive('roles')->times(4)->andReturn($relationship);
        $relationship->shouldReceive('lists')->times(4)->andReturn(array('admin', 'editor'));

        $this->assertTrue($model->isNot('user'));
        $this->assertFalse($model->isNot('admin'));

        $this->assertTrue($model->isNot(array('superadmin', 'user')));
        $this->assertFalse($model->isNot(array('admin', 'editor')));
    }

    /**
     * Test Orchestra\Support\Auth::isNot() method when invalid roles is
     * returned.
     *
     * @test
     */
    public function testIsNotMethodWhenInvalidRolesIsReturned()
    {
        $model = m::mock('\Orchestra\Model\User[roles]');
        $relationship = m::mock('\Illuminate\Database\Eloquent\Relations\BelongsToMany[lists]');

        $model->shouldReceive('roles')->times(4)->andReturn($relationship);
        $relationship->shouldReceive('lists')->times(4)->andReturn('foo');

        $this->assertTrue($model->isNot('admin'));
        $this->assertTrue($model->isNot('user'));

        $this->assertTrue($model->isNot(array('admin', 'editor')));
        $this->assertTrue($model->isNot(array('admin', 'user')));
    }

    /**
     * Test Orchestra\Model\User::isAny() method
     *
     * @test
     */
    public function testIsAnyMethod()
    {
        $model = m::mock('\Orchestra\Model\User[roles]');
        $relationship = m::mock('\Illuminate\Database\Eloquent\Relations\BelongsToMany[lists]');

        $model->shouldReceive('roles')->twice()->andReturn($relationship);
        $relationship->shouldReceive('lists')->twice()->andReturn(array('admin', 'editor'));

        $this->assertTrue($model->isAny(array('admin', 'user')));
        $this->assertFalse($model->isAny(array('superadmin', 'user')));
    }

    /**
     * Test Orchestra\Support\Auth::isAny() method when invalid roles is
     * returned.
     *
     * @test
     */
    public function testIsAnyMethodWhenInvalidRolesIsReturned()
    {
        $model = m::mock('\Orchestra\Model\User[roles]');
        $relationship = m::mock('\Illuminate\Database\Eloquent\Relations\BelongsToMany[lists]');

        $model->shouldReceive('roles')->twice()->andReturn($relationship);
        $relationship->shouldReceive('lists')->times(2)->andReturn('foo');

        $this->assertFalse($model->isAny(array('admin', 'editor')));
        $this->assertFalse($model->isAny(array('admin', 'user')));
    }

    /**
     * Test Orchestra\Model\User::isNotAny() method
     *
     * @test
     */
    public function testIsNotAnyMethod()
    {
        $model = m::mock('\Orchestra\Model\User[roles]');
        $relationship = m::mock('\Illuminate\Database\Eloquent\Relations\BelongsToMany[lists]');

        $model->shouldReceive('roles')->times(3)->andReturn($relationship);
        $relationship->shouldReceive('lists')->times(3)->andReturn(array('admin', 'editor'));

        $this->assertTrue($model->isNotAny(array('administrator', 'user')));
        $this->assertFalse($model->isNotAny(array('user', 'editor')));
        $this->assertFalse($model->isNotAny(array('admin', 'editor')));
    }

    /**
     * Test Orchestra\Support\Auth::isNotAny() method when invalid roles is
     * returned.
     *
     * @test
     */
    public function testIsNotAnyMethodWhenInvalidRolesIsReturned()
    {
        $model = m::mock('\Orchestra\Model\User[roles]');
        $relationship = m::mock('\Illuminate\Database\Eloquent\Relations\BelongsToMany[lists]');

        $model->shouldReceive('roles')->twice()->andReturn($relationship);
        $relationship->shouldReceive('lists')->twice()->andReturn('foo');

        $this->assertTrue($model->isNotAny(array('admin', 'editor')));
        $this->assertTrue($model->isNotAny(array('admin', 'user')));
    }

    /**
     * Test Orchestra\Model\User::scopeSearch() method.
     *
     * @test
     */
    public function testScopeSearchMethod()
    {
        $model = new User;
        $this->addMockConnection($model);

        $keyword = 'foo*';
        $search  = 'foo%';
        $roles   = array('admin');

        $query = m::mock('\Illuminate\Database\Eloquent\Builder');
        $query->shouldReceive('with')->once()->with('roles')->andReturn($query)
            ->shouldReceive('whereNotNull')->once()->with('users.id')->andReturn($query)
            ->shouldReceive('join')->once()->with('user_role', 'users.id', '=', 'user_role.user_id')->andReturn($query)
            ->shouldReceive('whereIn')->once()->with('user_role.role_id', $roles)->andReturn(null)
            ->shouldReceive('orWhere')->once()->with('email', 'LIKE', $search)->andReturn($query)
            ->shouldReceive('orWhere')->once()->with('fullname', 'LIKE', $search)->andReturn(null)
            ->shouldReceive('where')->once()->with(m::type('Closure'))->andReturnUsing(function ($q) use ($query, $keyword) {
                $q($query);
            });

        $this->assertEquals($query, $model->scopeSearch($query, $keyword, $roles));

    }

    /**
     * Test Orchestra\Model\User::getAuthIdentifier() method.
     *
     * @test
     */
    public function testGetAuthIdentifierMethod()
    {
        $stub = new User;
        $stub->id = 5;

        $this->assertEquals(5, $stub->getAuthIdentifier());
    }

    /**
     * Test Orchestra\Model\User::getAuthPassword() method.
     *
     * @test
     */
    public function testGetAuthPasswordMethod()
    {
        Hash::swap($hash = m::mock('\Illuminate\Hashing\HasherInterface'));

        $hash->shouldReceive('make')->once()->with('foo')->andReturn('foobar');

        $stub = new User;
        $stub->password = 'foo';

        $this->assertEquals('foobar', $stub->getAuthPassword());
    }

    /**
     * Test Orchestra\Model\User::getReminderEmail() method.
     *
     * @test
     */
    public function testGetReminderEmailMethod()
    {
        $stub = new User;
        $stub->email = 'admin@orchestraplatform.com';

        $this->assertEquals('admin@orchestraplatform.com', $stub->getReminderEmail());
    }

    /**
     * Test Orchestra\Model\User::getRecipientEmail() method.
     *
     * @test
     */
    public function testGetRecipientEmailMethod()
    {
        $stub = new User;
        $stub->email = 'admin@orchestraplatform.com';

        $this->assertEquals('admin@orchestraplatform.com', $stub->getRecipientEmail());
    }

    /**
     * Test Orchestra\Model\User::getRecipientName() method.
     *
     * @test
     */
    public function testGetRecipientNameMethod()
    {
        $stub = new User;
        $stub->fullname = 'Administrator';

        $this->assertEquals('Administrator', $stub->getRecipientName());
    }
}
