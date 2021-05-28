# Guide to setting the plugin
Plugin is ready for integration with jetEngine forms.

A. Setting for tokens' generation
  1. Create a form with Inputs:
      1. Token length (number of chars)   - name of the input : "tokenLength"
      2. Number of generated tokens       - name of the input : "tokenCount"
      3. ID of project assoc. with tokens - name of the input : "prProjectId"
      4. Hours of token expiration        - name of the input : "prExpirationHrs"
      5. Number of votes one token has    - name of the input : "prTokenVotes"
      Name if inputs can be changes in const INPUTS_FORM_GENERATE in header of paro.php file.
  2. Use value "generateTokens" in the callback function definition in the form.
      see add_filter('jet-engine-booking/filter/generateTokens', "prvt_generateTokens", 10, 4);
  3. Set correct post_type in the header of paro.php file.
      See define( 'PRVT_POST_TYPE',  "pr-respondenti");

B. Setting for token's validation and redirect to voting page
  1. Create a form with Inputs:
      1. Token value   - name of the input : "token"
      Name if inputs can be changes in const INPUTS_FORM_CHECKTOKEN in header of paro.php file.
  2. Use value "checkToken" in the callback function definition in the form.
      see add_filter('jet-engine-booking/filter/checkToken', "prvt_checkToken", 10, 4);
  3. Set correct slug of the voting page for redirect in the header of paro.php file.
      See define( 'PRVT_VOTING_PAGE', "hlasovani-paro");

C. Setting for votes' saving
  1. Create a form with Inputs:
      1. Token value  - name of the input : "token"
      2. Project ID   - name of the input : 'projekt'
      3. List of plus votes  - name of the input : 'plusa',
      4. List of minus votes - name of the input : 'minusa'
      5. Timestamp when voting started - name of the input : 'hlasovani_konec'
      6. Timestamp when voting ended   - name of the input : 'hlasovani_zacatek'
      Name if inputs can be changes in const INPUTS_FORM_VOTE in header of paro.php file.
  2. Use value "saveVotes" in the callback function definition in the form.
      see   add_filter('jet-engine-booking/filter/saveVotes', "prvt_saveVotes", 10, 4);


# ChangeLog

## 0.1.0

* Initial release;
