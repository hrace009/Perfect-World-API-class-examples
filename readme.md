# Perfect World API class + examples
A php script to use on Perfect World gaming environment.

Original made by <a href="http://forum.ragezone.com/members/861743.html">Goodlookinguy</a> on <a href="http://forum.ragezone.com/f694/php-perfect-world-api-class-818826/">RageZone</a>, or <a href="https://github.com/Goodlookinguy">Goodlookinguy</a> at Github

Documentation Click <a href="https://hrace009.github.io/Perfect-World-API-class-examples/">Here</a>

Message From Creator:<br>
> I was working on this php-based Perfect World API months ago. Now that I have time again, I decided to pick this project of mine again...and then just decided to rewrite everything into a better system all today.

> So I proudly present PerfectWorldAPI class in php. This php class will help you by doing almost everything for you. Creating accounts, logging in, getting user data, user characters, GM lists, etc. As well as auto-filtering when you create users or log in. This can easily fit into any small or large site for PW.

> I recommend you use Zend Studio for Eclipse or a php IDE that can read all of the methods from the class. If you don't have that, you can muddle through the generated documentation.

Steps you MUST follow to get it running:
<ul>
<li>Go into /includes/ and open up classPerfectWorldAPI.php</li>
<li>Put in your database information into the provided areas (which are clearly documented)</li>
</ul>


When making your content, you must define the constant ROOT as the location that the files are to be pulled from. Then include the files as shown below. Examples show this as well.
```
/* -------------------------------------------------- */
 if ( !defined('ROOT') )
     define('ROOT', '/var/www/');
 require_once(ROOT . 'includes/classPerfectWorldAPI.php');
 /* -------------------------------------------------- */
 
 // Your code goes here!
 ```
 
## Q & A
**Something is broken or it's not working!**<br>
Please give me the error. Sometimes I break code, don't test it, and then release it. Therefore if you have that problem, alert me immediately so I can fix it promptly.

**What on earth is Monkey?**<br>
It's a library based on a programming language called Monkey, that I went and took a lot further with php. Including classes that definitely weren't in that language. One last thing, yes, the Monkey library is a dependency, so DON'T leave it out.

**Is there going to be more functionality?**<br>
Yes! I'm happy to report that this is only one day of work. What if we expand that to two?

**What if I see a security issue?**<br>
Point it out. I'll attempt to see if it's actually a security issue. If it is, I'll fix it promptly.

**I read the license, I disagree with it.**<br>
That's fine, I really don't care.

**I'd like to contribute to the project, where can I do that?**<br>
Here. It's pretty simple, write a response with the code in it. I'll review it and might add it.

**Can I ask for a feature?**<br>
You can. Whether or not I'll be able to accomplish said feature is questionable.

## Updates
```
2/12/2012 - rev. 1
- License agreement was changed to be more suited for websites and their footers.

2/13/2012
- Tons of bug fixes, including one to AddUser
- AddGMByUsername, AddGMByID Added
- Other methods that I've forgotten the name of added

2/13/2012 - rev. 1
- Quick fix at the broken code at the top of classPerfectWorldAPI.php

2/16/2012
- Fixed method LoginByEmail
- Optimzed method GetOnlineCharList
- Optimized and added parameters 'limit' and 'page' to method GetCharList

2/17/2012
- Fixed bad logic in UsernameExists and EmailExists
- Added method SetPassword

2/22/2012
- Deprecated GetOnlineCharList and GetOnlineCharacterList, SQL cannot work

11/20/2012
- Fixed error that could cause session to endlessly error-out until session cookie was deleted or expired

12/23/2019
- Add new monkey libs 2019
```
