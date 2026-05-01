document.addEventListener("DOMContentLoaded", function () {

    loadResources();

});

function createResourceArticle(resource) {

    const article = document.createElement("article");

    article.innerHTML = `

        <h2>${resource.title}</h2>

        <p>${resource.description}</p>

        <a href="details.html?id=${resource.id}">View Details</a>

    `;

    return article;

}

async function loadResources() {

    const section = document.getElementById("resource-list-section");

    if (!section) {

        return;

    }

    try {

        const response = await fetch("./api/index.php");

        const result = await response.json();

        const resources = result.data || result.resources || [];

        section.innerHTML = "";

        resources.forEach(function (resource) {

            section.appendChild(createResourceArticle(resource));

        });

    } catch (error) {

        section.innerHTML = "";

    }

}
