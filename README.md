# Lazy

**Lazy** is a simple PHP library that allows you to transform data from JSON arrays into PHP objects (POPOs), or copy data between two PHP classes with ease — optionally with type autocasting.

---

## 🚀 Features

- Transform associative arrays (e.g., from JSON) into PHP objects
- Copy data from one PHP object to another
- Autocast values to match property types using docblocks
- Lightweight, no external dependencies

---

## 🧱 Example: Array to Object

Suppose you have the following array:

```php
$profileArray = [
    'name' => 'John Doe',
    'age' => '17'
];
```

And a plain PHP class (POPO) like this:

```php
<?php

class Profile
{
    public string $name = '';
    public int $age = 0;
}
```

You can map the array to the object using Lazy:

```php
<?php

$profile = new Profile();
$profile = Lazy::copyFromArray($profileArray, $profile);

echo $profile->name; // John Doe
```

---

## 🔄 Example: Object to Object

You can also copy values between two PHP objects:

```php
<?php

class Member
{
    public string $name = '';
    public int $age = 0;
}

class Profile
{
    public string $name = '';
    public int $age = 0;
}

$member = new Member();
$member->name = 'John Doe';
$member->age = 17;

$profile = new Profile();
$profile = Lazy::transform($member, $profile);

echo $profile->name; // John Doe
```

---

## 💬 Support

If you have any questions or feedback, feel free to reach out:

- 📧 Email: [dyan.galih@gmail.com](mailto:dyan.galih@gmail.com)
- 💬 Telegram: [@DyanGalih](https://t.me/DyanGalih)

---

## 🧘 Happy Coding!

> Be Lazy. Be Efficient. Happy Coding with **Lazy**!
