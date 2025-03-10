# Documentation Checker-API

## Request

```
GET https://{YOUR-DOMAIN}/comment/{AUTH-TOKEN}
```

The following attributes hereby have to be transmitted:

| Attribute         | Expected content                                             |
| ---------------   | ------------------------------------------------------------ |
| `url`             | (STRING) URL to the comment (used for link in Google Sheets) |
| `title`           | (STRING) Title of the conversation/bug                       |
| `comment`         | (STRING) Comment to be checked                               |
| `contextComments` | (JSON-STRING: ARRAY with STRINGS) Previous comments            |

### Example request
```Python
import requests, json

res = requests.get('https://{YOUR-DOMAIN}/comment/{AUTH-TOKEN}', {
	"url": "https://bugzilla.mozilla.org/show_bug.cgi?id={XXX}",
	"title": "Test",
	"contextComments": json.dumps([
		"Hello, this is a test.",
		"Hey there, that's a test too :)"
	]),
	"comment": "This is a very very rude test comment!!!"
})
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
Additionally: A new line gets created in Google Docs for moderators.
## Exceptions
- If the request to ChatGPT took more than 20s: `Error 408`
- If `AUTH-TOKEN` wrong: `Eror 401`
- Else `Error 500` with corresponding error message