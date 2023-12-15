make a post

POST BOARDURI/post
fields:
  thread (hidden)
  name (text)
  email (text)
  sage (checkbox)
  subject (text)
  message (text)
  postpassword
  files (file)
  captcha_id (number)
  captcha (text)

Get an index of threads
/opt/BOARDURI/catalog.json
/opt/boards/BOARDURI/1

&prettyPrint=1 is optional, just makes the output human readable. Suggest to remove in code
