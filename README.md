# Lazy

Lazy is library to transform from json object into php class or from php class into php class. 

For example, You have an array data like this:
````
    $profileArray =[
        'name' => 'John Doe',
        'age' => '17'
    ]
````

You just need create a POPO file like this:
````
<?php

class Profile{

    /**
    * @var string
    */
    public $name;

    /**
    * @var int
    */
    public $age;
}
````

What you do just create script like this:
````
<?php

    $profile = new Profile();

    $profile = Lazy::copyFromArray($profileArray, $profile, Lazy::AUTOCAST);

    echo $profile->name
````

If you want to copy data from any php class into any other php class you can do like this.

For example origin php class file:

````
<?php
class Member{
    
    /**
    * @var string
    */
    public $name;

    /**
    * @var int
    */
    public age;
}
````

For the destination php class file like this:
````
<?php

class Profile{

    /**
    * @var string
    */
    public $name;

    /**
    * @var int
    */
    public age;
}
````

You can create lazy script like this:

````
<?php

$member = new Member();
$member->name = 'John Doe';
$member->age = 17;

$profile = new Profile();
$profile Lazy::copy($member, $profile, Lazy::AUTOCAST);

echo $profile->name;

````

It's very easy, isn't it?

If you have any question related this package, please don't hesitate to chat me at telegram @DyanGalih or drop me an email at dyan.galih@gmail.com

Happy Coding, Happy Lazy, and be a Lazy Coder. 