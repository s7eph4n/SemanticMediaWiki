# How to contribute

You are welcome to contribute to [Semantic MediaWiki][smw] (a.k.a. SMW). There are a lot of ways to do so which are listed
in this document. A few guidelines need to be followed so that we can have a chance of keeping on top of things.

## As a user

* You may help us by reporting bugs and feature requests via the [issue tracker][smw-issues]. See the help page on [reporting bugs][smw-bugs1] as well as on [identifying bugs][smw-bugs2] for information on how to do this best.
* You may help us by doing [pre-release testing][smw-testing] by joinging the [team of testers][smw-testers] on GitHub.
* You may help us by providing translations for system messages via [translatewiki.net][twn]. See their [progress-statistics][twn-smw] to see if there is still work to do for your language.
* You may help us by creating, updating or amending the documentation of the software on [Semantic-MediaWiki.org][smw].


## As a developer

Please ensure that pull requests are based on the current master. See also the [developer documentation overview][smwddo].

In order to swiftly co-ordinate your code contribution, the following should be provided:
- Code should be easily read and if necessary be put into separate components (or classes)
- Newly added features should not alter an existing test but instead provide additional test coverage to verify the
expected behaviour

For a description on how to write and run PHPUnit test, please consult the [manual][mw-testing].

[smw]: https://github.com/SemanticMediaWiki/SemanticMediaWiki
[smw-issues]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues
[smw-bugs1]: https://semantic-mediawiki.org/wiki/Help:Reporting_bugs
[smw-bugs2]: https://semantic-mediawiki.org/wiki/Help:Identifying_bugs
[smw-testing]: https://semantic-mediawiki.org/wiki/Help:Reporting_bugs#Pre-release_testing
[smw-testers]: https://github.com/orgs/SemanticMediaWiki/teams/testers
[twn]: https://translatewiki.net/
[twn-smw]: https://translatewiki.net/w/i.php?title=Special%3AMessageGroupStats&x=D&group=ext-semanticmediawiki&suppressempty=1
[smw-ddo]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/contribute/docs/technical/README.md
[mw-testing]: https://www.mediawiki.org/wiki/Manual:PHP_unit_testing
