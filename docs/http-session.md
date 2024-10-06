# HTTP Session

A session is a short term storage specific to one visitor of your website.

It is typically used to store short-term information like flash data or CSRF tokens but also the fact that the user is logged-in.

And typically the session id is stored on the user's side via a cookie.

So the session isn't much more than an entity that is saved in a storage.

Session (the data)
SessionMiddleware (the starts the session, reads the cookie, retrieve the correct one)
SessionBackend
- PDO
- FileSystem