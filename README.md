# Requested and Required Fields

This REDCap External Module provides functionality for requesting that respondents provide an answer to a field, and displays a warning to survey respondents if any requested fields are missing a value when they try to submit. For completeness, this module also allows required fields to be treated in the same way. Fields can be annotated with @REQUESTED or @REQUIRED, both of which take an optional description, which is shown to the user when they try to submit. If the description is omitted, the field label is shown instead.

![screencast](screencast.gif)

This module respects fields marked @HIDDEN(-SURVEY) and also those that are hidden due to branching logic.

## Limitations

This module does not play nicely with embedded fields. Perhaps this could be fixed, but at the moment it's just a limitation.

Unlike traditional required fields, wherein submitting the page commits other values to the database and sets the survey as partially complete, with this module enabled, clicking 'submit' does _not_ save any other entered values. Again, perhaps this could be fixed by running an AJAX call to save the data.

This module only considers fields to be required if they are annotated with @REQUIRED. It might be an idea in future to take fields marked Required in the designer and treat them in the same way. 

## Installation

Install the module from the REDCap module repository and enable in the Control Center, then enable on projects as needed.

## Usage

This module adds two action tags:

| Action Tag | Description |
| --- | --- |
| @REQUESTED | Displays a modal window if the annotated field is empty when the respondent attempts to submit, but allows the respondent to submit regardless (unless there are @REQUIRED fields). With a description provided by @REQUESTED="description", the description is shown in the modal. Otherwise, the field label is shown. |
| @REQUIRED | Displays a modal window if the annotated field is empty when the respondent attempts to submit, and prevents submission. With a description provided by @REQUIRED="description", the description is shown in the modal. Otherwise, the field label is shown. |

## Configuration

This module can be configured with the following project settings:

| Setting | Default Value | Description |
| --- | --- | --- |
| Modal title | "Action Required!" | Title of the popup window showing any requested or required fields. |
| Requested text |  "The following fields are requested, although you may submit without completing them:" | Text displayed in the modal window above listed requested fields. |
| Required text |  "The following fields are required:" | Text displayed in the modal window above listed requested fields. |
| Footer text (no required fields) |  "The following fields are requested, although you may submit without completing them:" | Text displayed at the end of the modal, above the buttons, where no required fields are missing. |
| Footer text (required fields) |  "The following fields are required:" | Text displayed at the end of the modal, above the buttons, where required fields are missing. |
| Cancel button text | "Review Response" | Text for cancel button. |
| Submit button text | "Submit Now" | Text for submit button. |
| Highlight fields after displaying warning? | false | If true, highlights required and requested fields after cancelling the modal window. |
| Highlight colour for requested fields | "#d2e0ff" (light blue) | Colour used to highlight requested fields. |
| Highlight colour for required fields | "#ffd2e0" (light red) | Colour used to highlight required fields. |
| Disable green highlight | false | Disable the default green highlighting on all fields, as this will visually conflict with the highlighting added by this module. |
| Label requested fields | false | Display a label on requested fields. |
| Requested field label text | "* response requested" | Text for requested field label. |
| Requested field label colour | "#0000ff" (blue) | Colour for requested field label. |
