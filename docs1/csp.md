# Content Security Policy (CSP)

Configure Content Security Policy Level 3 features

![Content security policy settings](./img/content-security-policy-settings.PNG)

## Enable CSP

Configure CSP policies. Values are

- Disabled: disable CSP feature
- Report Only: CSP settings are not enforced but any violation is sent to the url configured with _Report URL_ setting
- Enforce: apply the CSP settings

## Report URL

CSP violation reports are sent to this url

## Base URI Policy

Restricts the urls which can be used in a document's \<base\> element. Values are

- Ignore: do not set tbe value
- 'Self': set the value to 'Self'
- None: set the value to _'none'_

## Allow Javascript 'eval'

Enable or disable javascript _'eval'_ function

## Allow Inline Scripts

Control inline scripts execution. Values are

- None: block all inline scripts
- Yes: this will allow any script with the _'nonce'_ attribute, every other scripts will be blocked
- Yes, Backward Compatible: compatibility mode. this will enable all the scripts

## Allow inline CSS

Control inline style. Values are

- None: block all inline styles
- Yes: this will allow any style with the _'nonce'_ attribute, every other styles will be blocked
- Yes, Backward Compatible: compatibility mode. this will enable all the styles

## Default Directive

Configure _default-src_ directive. Values are

- Ignore: ignore the directive
- Block: block everything by default
- Custom: use whatever is provided in _Custom Settings_

## Custom Settings

Configure additional settings for _default-src_

## Scripts

Configure _script-src_ directive. Values are

- Ignore: ignore the directive
- Dynamic: parse the page and add links to the whitelist
- Block: block scripts
- Custom: use whatever is provided in _Custom Script Settings_
- Mixed: append the value in _Custom Script Settings_ to this setting

## Custom Script Settings

Configure additional settings for _script-src_

## Styles

Configure _style-src_ directive. Values are

- Ignore: ignore the directive
- Block: block styles by default
- Dynamic: parse the page and add links to the whitelist
- Custom: use whatever is provided in _Custom Styles Settings_
- Mixed: append the value in _Custom Styles Settings_ to this setting

## Custom Styles Settings

Configure additional settings for _style-src_

## Font Directive

Configure _font-src_ directive. Values are

- Ignore: ignore the directive
- Block: block fonts by default
- Dynamic: parse the page and add links to the whitelist
- Custom: use whatever is provided in _Custom Font Settings_
- Mixed: append the value in _Custom Styles Settings_ to this setting

## Custom Font Settings

Configure additional settings for _font-src_

## Image Directive

Configure _img-src_ directive. Values are

- Ignore: ignore the directive
- Block: block images by default
- Dynamic: parse the page and add links to the whitelist
- Custom: use whatever is provided in _Custom Font Settings_
- Mixed: append the value in _Image Custom Settings_ to this setting

## Custom Image Settings

Configure additional settings for _worker-src_

## Worker Directive

Configure _worker-src_ directive. Values are

- Ignore: ignore the directive
- Block: block workers by default
- Custom: use whatever is provided in _Custom Worker Settings_

## Custom Worker Settings

Configure additional settings for _worker-src_

## Manifest Directive

Configure _manifest-src_ directive. Values are

- Ignore: ignore the directive
- Block: block manifest files by default
- Custom: use whatever is provided in _Custom Manifest Settings_

## Custom Manifest Settings

Configure additional settings for _manifest-src_

## Child Directive

Configure _child-src_ directive. Values are

- Ignore: ignore the directive
- Block: block everything by default
- Custom: use whatever is provided in _Custom Settings_

## Custom Child Settings

Configure additional settings for _child-src_

## Frame Directive

Configure _frame-src_ directive. Values are

- Ignore: ignore the directive
- Block: block frames and iframes by default
- Dynamic: parse the page and add links to the whitelist
- Custom: use whatever is provided in _Custom Font Settings_
- Mixed: append the value in _Custom Frame Settings_ to this setting

## Custom Frame Settings

Configure additional settings for _frame-src_

## Object Settings

Configure _object-src_ directive. Values are

- Ignore: ignore the directive
- Block: block all object by default
- Custom: use whatever is provided in _Custom Settings_

## Custom Object Settings

Configure additional settings for _object-src_

## Media Settings

Configure _media-src_ directive. Values are

- Ignore: ignore the directive
- Block: block all media by default
- Custom: use whatever is provided in _Custom Media Settings_

## Custom Media Settings

Configure additional settings for _media-src_

## Prefetch Settings

Configure _prefetch-src_ directive. Values are

- Ignore: ignore the directive
- Block: block prefetch requests
- Custom: use whatever is provided in _Custom Settings_

## Custom Prefetch Settings

Configure additional settings for _prefetch-src_

## Connect Settings

Configure _connect-src_ directive. Values are

- Ignore: ignore the directive
- Block: block connect requests
- Custom: use whatever is provided in _Custom Connect Settings_

## Custom Connect Settings

Configure additional settings for _connect-src_
