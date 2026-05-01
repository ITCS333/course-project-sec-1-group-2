/*
  Requirement: Populate the assignment detail page and discussion forum.

  Instructions:
  1. This file is already linked to `details.html` via:
         <script src="details.js" defer></script>

  2. The following ids must exist in details.html (already listed in the
     HTML comments):
       #assignment-title       — <h1>
       #assignment-due-date    — <p>
       #assignment-description — <p>
       #assignment-files-list  — <ul>
       #comment-list           — <div>
       #comment-form           — <form>
       #new-comment            — <textarea>

  3. Implement the TODOs below.

  API base URL: ./api/index.php
  Assignment object shape returned by the API:
    {
      id:          number,   // integer primary key from the assignments table
      title:       string,
      due_date:    string,   // "YYYY-MM-DD" — matches the SQL column name
      description: string,
      files:       string[]  // decoded array of URL strings
    }

  Comment object shape returned by the API
  (from the comments_assignment table):
    {
      id:            number,
      assignment_id: number,
      author:        string,
      text:          string,
      created_at:    string
    }
*/

// --- Global Data Store ---
let currentAssignmentId = null;
let currentComments     = [];

// --- Element Selections ---
// TODO: Select each element by its id:
//   assignmentTitle, assignmentDueDate, assignmentDescription,
//   assignmentFilesList, commentList, commentForm, newCommentInput.

// --- Functions ---
const assignmentTitle = document.querySelector('#assignment-title');
const assignmentDueDate = document.querySelector('#assignment-due-date');
const assignmentDescription = document.querySelector('#assignment-description');
const assignmentFilesList = document.querySelector('#assignment-files-list');
const commentList = document.querySelector('#comment-list');
const commentForm = document.querySelector('#comment-form');
const newCommentInput = document.querySelector('#new-comment');

/**
 * TODO: Implement getAssignmentIdFromURL.
 *
 * It should:
 * 1. Read window.location.search.
 * 2. Construct a URLSearchParams object from it.
 * 3. Return the value of the 'id' parameter (a string that represents
 *    the integer primary key of the assignment).
 */
function getAssignmentIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get('id');
}

/**
 * TODO: Implement renderAssignmentDetails.
 *
 * Parameters:
 *   assignment — the assignment object returned by the API (see shape above).
 *
 * It should:
 * 1. Set assignmentTitle.textContent       = assignment.title.
 * 2. Set assignmentDueDate.textContent     = "Due: " + assignment.due_date.
 *    (Note: use assignment.due_date, which matches the SQL column name.)
 * 3. Set assignmentDescription.textContent = assignment.description.
 * 4. Clear assignmentFilesList, then for each URL in assignment.files:
 *    - Create a <li> containing an <a href="{url}">{url}</a>.
 *    - Append the <li> to assignmentFilesList.
 *    (assignment.files is already a decoded string array from the API.)
 */
function renderAssignmentDetails(assignment) {
  assignmentTitle.textContent = assignment.title;
  assignmentDueDate.textContent = `Due: ${assignment.due_date}`;
  assignmentDescription.textContent = assignment.description;

  assignmentFilesList.innerHTML = '';
  if (Array.isArray(assignment.files)) {
    assignment.files.forEach((url) => {
      const li = document.createElement('li');
      const link = document.createElement('a');
      link.href = url; // FIXED
      link.textContent = url;
      li.appendChild(link);
      assignmentFilesList.appendChild(li);
    });
  }
}

/**
 * TODO: Implement createCommentArticle.
 *
 * Parameters:
 *   comment — one comment object from the API:
 *     { id, assignment_id, author, text, created_at }
 *
 * Returns an <article> element:
 *   <article>
 *     <p>{comment.text}</p>
 *     <footer>Posted by: {comment.author}</footer>
 *   </article>
 */
function createCommentArticle(comment) {
  const article = document.createElement('article');
  article.classList.add('comment');

  const p = document.createElement('p');
  p.textContent = comment.text;

  const footer = document.createElement('footer');
  footer.textContent = `Posted by: ${comment.author}`;

  article.appendChild(p);
  article.appendChild(footer);

  return article;
}

/**
 * TODO: Implement renderComments.
 *
 * It should:
 * 1. Clear commentList (set innerHTML to "").
 * 2. Loop through currentComments.
 * 3. For each comment, call createCommentArticle(comment) and
 *    append the result to commentList.
 */
function renderComments() {
  commentList.innerHTML = '';
  currentComments.forEach((comment) => {
    const commentArticle = createCommentArticle(comment);
    commentList.appendChild(commentArticle);
  });
}

/**
 * TODO: Implement handleAddComment (async).
 *
 * This is the event handler for commentForm's 'submit' event.
 * It should:
 * 1. Call event.preventDefault().
 * 2. Read and trim the value from newCommentInput (#new-comment).
 * 3. If the value is empty, return early (do nothing).
 * 4. Send a POST to './api/index.php?action=comment' with the body:
 *      {
 *        assignment_id: currentAssignmentId,   // integer
 *        author:        "Student",             // hardcoded for this exercise
 *        text:          commentText
 *      }
 *    The API inserts a row into the comments_assignment table.
 * 5. On success (result.success === true):
 *    - Push the new comment object (from result.data) onto
 *      currentComments.
 *    - Call renderComments() to refresh the list.
 *    - Clear newCommentInput.
 */
async function handleAddComment(event) {
  event.preventDefault();

  const commentText = newCommentInput.value.trim();
  if (!commentText) return;

  const response = await fetch('./api/index.php?action=comment', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      assignment_id: parseInt(currentAssignmentId, 10),
      author: 'Student',
      text: commentText
    })
  });

  const result = await response.json();
  if (result.success) {
    currentComments.push(result.data); // use API response
    renderComments();
    newCommentInput.value = ''; // FIXED clear textarea
  }
}
/**
 * TODO: Implement initializePage (async).
 *
 * It should:
 * 1. Call getAssignmentIdFromURL() and store the result in
 *    currentAssignmentId.
 * 2. If currentAssignmentId is null or empty, set
 *    assignmentTitle.textContent = "Assignment not found." and return.
 * 3. Fetch both the assignment details and its comments in parallel using
 *    Promise.all:
 *      - Assignment: GET ./api/index.php?id={currentAssignmentId}
 *                    Response: { success: true, data: { ...assignment object } }
 *      - Comments:   GET ./api/index.php?action=comments&assignment_id={currentAssignmentId}
 *                    Response: { success: true, data: [ ...comment objects ] }
 *    Comments are stored in the comments_assignment table
 *    (columns: id, assignment_id, author, text, created_at).
 * 4. Store the comments array in currentComments
 *    (use an empty array if none exist).
 * 5. If the assignment was found:
 *    - Call renderAssignmentDetails(assignment).
 *    - Call renderComments().
 *    - Attach the 'submit' listener to commentForm (calls handleAddComment).
 * 6. If the assignment was not found:
 *    - Set assignmentTitle.textContent = "Assignment not found."
 */
async function initializePage() {
  currentAssignmentId = getAssignmentIdFromURL();

  if (!currentAssignmentId) {
    assignmentTitle.textContent = 'Assignment not found.';
    return;
  }

  try {
    const [assignmentRes, commentsRes] = await Promise.all([
      fetch(`./api/index.php?id=${currentAssignmentId}`),
      fetch(`./api/index.php?action=comments&assignment_id=${currentAssignmentId}`)
    ]);

    const assignmentResult = await assignmentRes.json();
    const commentsResult = await commentsRes.json();

    if (!assignmentResult.success || !assignmentResult.data) {
      assignmentTitle.textContent = 'Assignment not found.';
      return;
    }

    const assignment = assignmentResult.data;
    currentComments = commentsResult.success ? commentsResult.data : [];

    renderAssignmentDetails(assignment);
    renderComments();

    commentForm.addEventListener('submit', handleAddComment);
  } catch (error) {
    console.error('Error initializing page:', error);
    assignmentTitle.textContent = 'Error loading assignment.';
  }
}

// --- Initial Page Load ---
initializePage();
