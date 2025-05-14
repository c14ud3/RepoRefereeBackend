# Documentation Checker-API

## Request

```
POST https://{YOUR-DOMAIN}/comment/{AUTH-TOKEN}
```

The following attributes have to be transmitted in the body of the request with content-type JSON:

| Attribute         | Expected content                                             |
| ---------------   | ------------------------------------------------------------ |
| `url`             | (STRING) URL to the comment (used for link in moderation UI) |
| `title`           | (STRING) Title of the conversation/bug                       |
| `comment`         | (STRING) Comment to be checked                               |
| `contextComments` | (ARRAY with strings) Previous comments                       |

### Example request
```Python
import requests, json

res = requests.post(
	'https://{YOUR-DOMAIN}/comment/{AUTH-TOKEN}',
	data=json.dumps({
		"url": "https://bugzilla.mozilla.org/show_bug.cgi?id={XXX}",
		"title": "Test",
		"contextComments": [
			"Hello, this is a test.",
			"Hey there, that's a test too :)"
		],
		"comment": "This is a very very rude test comment!!!"
	}),
	headers={
		'Content-Type': 'application/json'
	}
)
```

## Response
If everything fine:
```JSON
{
  "TEXT_TOXICITY": true,
  "TOXICITY_REASONS": "The text contains vulgarity due to the use of the expletive 'stupid', which is considered improper language.",
  "VIOLATED_GUIDELINE": "The text violates the guideline of being respectful and using derogatory language, as it includes profanity which is not acceptable.",
  "REPHRASED_TEXT_OPTIONS": [
    "Why do I always need to terminate this task?",
    "Why is it necessary for me to keep ending this task?",
    "Why do I have to keep stopping this task?"
  ]
}
```
Additionally: The comment is added to the moderation UI.
If the same request has already been sent before (meaning `url` and `comment` are equal), the response is the same as above, but the comment is not added to the moderation UI again.
## Exceptions
- If the request to ChatGPT took more than 20s: `Error 408`
- If `AUTH-TOKEN` wrong: `Eror 401`
- Else `Error 500` with corresponding error message