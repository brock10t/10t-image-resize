# GitHub and WordPress.org releases

Development lives on GitHub. Tagged releases are published using [10up's deployment action](https://github.com/10up/action-wordpress-plugin-deploy).

## Initial setup

1. Add your WordPress.org username as the `Contributors:` field in `readme.txt`.
2. Submit a plugin ZIP for WordPress.org review. Include only `10t-web-design-image-resize.php`, `readme.txt`, and `LICENSE`, inside a `10t-web-design-image-resize` folder.
3. After approval, confirm the assigned plugin slug. If it differs, update the plugin text domain and release packaging accordingly.
4. In GitHub, open Settings > Secrets and variables > Actions. Add repository secrets `SVN_USERNAME` and `SVN_PASSWORD` using your WordPress.org SVN credentials. Never commit credentials. Use the SVN password provided by WordPress.org account security settings.
5. Add the repository variable `WP_ORG_SLUG` with the approved slug. Until this variable exists, the deployment job skips publishing.
6. Ensure GitHub Actions is enabled for the repository.

## Each release

1. Run checks and finish upload testing.
2. Update `Version:` in the plugin header, `Stable tag:` in `readme.txt`, and its changelog.
3. Commit and push to GitHub.
4. Create and push a matching tag, for example `git tag 1.0.0` followed by `git push origin 1.0.0`.
5. Check the Actions run and verify the resulting WordPress.org listing.

Only numeric `major.minor.patch` tags are accepted. Ordinary branch pushes do not publish. The workflow checks matching versions, then updates SVN trunk and the release tag. Do not reuse a published version tag; release a new version for corrections. The `.distignore` file excludes development files from SVN deployment.

Tags pushed before configuring `WP_ORG_SLUG` will be skipped; configure publishing before pushing the first release tag. Creating a GitHub Release for an existing tag does not trigger this workflow.
