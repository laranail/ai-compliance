---
title: "{{company}} training-data statement"
type: policy
---

Content on {{domain}} {{"may" / "may not"}} be used for AI model training by third parties. We express this preference in machine-readable form (robots.txt and llms.txt below) and, for EU purposes, as a reservation of rights under Article 4(3) of Directive (EU) 2019/790 where we've opted out of text and data mining. These signals state our preference; they aren't technical enforcement, and we pursue misuse through the terms of service at {{terms_url}}.

## Machine-readable signals

robots.txt block for a no-training stance (edit the agent list to the operator's actual choice, and drop this block entirely if the stance is "allowed"):

```
# ai training crawlers: adjust to your policy, this example opts out
User-agent: GPTBot
Disallow: /

User-agent: ClaudeBot
Disallow: /

User-agent: Google-Extended
Disallow: /

User-agent: CCBot
Disallow: /
```

llms.txt skeleton (optional, model-facing guidance rather than a standard with legal force):

```
# {{company}}
> {{one-line description of the site}}

## Policy
- Training use of this site's content: {{allowed | not allowed | contact us}}
- Contact: {{contact_email}}

## Key pages
- [{{page}}]({{url}}): {{what it is}}
```

## California training-data documentation

If we (not a vendor) develop a public generative AI system reaching California users, we add the AB 2013 training-data documentation page: sources and categories of training data at a high level, whether personal or copyrighted data is included, collection period, and cleaning/processing applied. As deployers only, we instead file each vendor's version of this in the provider registry.
