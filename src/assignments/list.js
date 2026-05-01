/*
  Requirement: Populate the "Course Assignments" list page.

  Instructions:
  1. This file is already linked to `list.html` via:
         <script src="list.js" defer></script>

  2. In `list.html`, the <section id="assignment-list-section"> is the
     container that this script populates.

  3. Implement the TODOs below.

  API base URL: ./api/index.php
  Successful list response shape: { success: true, data: [ ...assignment objects ] }
  Each assignment object shape:
    {
      id:          number,   // integer primary key from the assignments table
      title:       string,
      due_date:    string,   // "YYYY-MM-DD" — matches the SQL column name
      description: string,
      files:       string[]  // already decoded array of URL strings
    }
*/

// --- Element Selections ---
// TODO: Select the section for the assignment list using its
//       id 'assignment-list-section'.
const listSection = document.querySelector('#assignment-list-section');
// --- Functions ---

/**
 * TODO: Implement createAssignmentArticle.
 *
 * Parameters:
 *   assignment — one object from the API response with the shape:
 *     {
 *       id:          number,
 *       title:       string,
 *       due_date:    string,   // "YYYY-MM-DD" — use due_date, not dueDate
 *       description: string,
 *       files:       string[]
 *     }
 *
 * Returns:
 *   An <article> element matching the structure shown in list.html:
 *     <article>
 *       <h2>{title}</h2>
 *       <p>Due: {due_date}</p>
 *       <p>{description}</p>
 *       <a href="details.html?id={id}">View Details &amp; Discussion</a>
 *     </article>
 *
 * Important: the href MUST be "details.html?id=<id>" (integer id from
 * the assignments table) so that details.js can read the id from the URL.
 */
function createAssignmentArticle(assignment) {
  const { id, title, due_date, description } = assignment;
  
  const article = document.createElement('article');

  const h2 = document.createElement('h2');
  h2.textContent = title;

  const dueP = document.createElement('p');
  dueP.innerHTML = `<strong>Due:</strong> ${due_date}`;

  const descP = document.createElement('p');
  descP.textContent = description;

  const link = document.createElement('a');
  link.href = `details.html?id=${id}`;
  link.textContent = 'View Details & Discussion';
  
  article.appendChild(h2);
  article.appendChild(dueP);
  article.appendChild(descP);
  article.appendChild(link);

  return article;
}

/**
 * TODO: Implement loadAssignments (async).
 *
 * It should:
 * 1. Use fetch() to GET data from './api/index.php'.
 *    The API returns JSON in the shape:
 *      { success: true, data: [ ...assignment objects ] }
 * 2. Parse the JSON response.
 * 3. Clear any existing content from the list section.
 * 4. Loop through the data array. For each assignment object:
 *    - Call createAssignmentArticle(assignment).
 *    - Append the returned <article> to the list section.
 */
async function loadAssignments() {
  try {
    // 1. Fetch data from API
    const response = await fetch('./api/index.php');
    if (!response.ok) {
      throw new Error('Failed to load assignments');
    }

    const result = await response.json();
    if (!result.success) {
      throw new Error('API returned an error');
    }

    const assignments = result.data;

    // 3. Clear existing content
    listSection.innerHTML = '';

   // 4. Loop through assignments
    assignments.forEach((assignment) => {
      const article = createAssignmentArticle(assignment);
      listSection.appendChild(article);
    });
  } catch (error) {
    console.error('Error loading assignments:', error);
    listSection.innerHTML = '<p>Failed to load assignments.</p>';
  }
}

// --- Initial Page Load ---
loadAssignments();
