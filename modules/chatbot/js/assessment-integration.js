// Assessment Integration functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize form handling
    const form = document.getElementById('selectStudent');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            loadStudentData();
        });
    }

    // Initialize student select
    const studentSelect = document.querySelector('select[name="gibbonPersonID"]');
    if (studentSelect) {
        studentSelect.addEventListener('change', function() {
            if (this.value) {
                loadStudentData();
            }
        });
    }
});

function loadStudentData() {
    const gibbonPersonID = document.querySelector('select[name="gibbonPersonID"]').value;
    if (!gibbonPersonID) return;

    // Show loading state
    document.querySelector('.assessment-data').innerHTML = '<div class="loading">Loading student data...</div>';

    // Submit the form
    document.getElementById('selectStudent').submit();
}

function refreshAIRecommendations(gibbonPersonID) {
    const recommendationsContainer = document.querySelector('.ai-recommendations');
    if (!recommendationsContainer) return;

    fetch(`ajax/get_recommendations.php?gibbonPersonID=${gibbonPersonID}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                recommendationsContainer.innerHTML = formatRecommendations(data.recommendations);
            } else {
                recommendationsContainer.innerHTML = '<div class="error">Failed to load recommendations</div>';
            }
        })
        .catch(error => {
            console.error('Error loading recommendations:', error);
            recommendationsContainer.innerHTML = '<div class="error">Error loading recommendations</div>';
        });
}

function formatRecommendations(recommendations) {
    let html = '<div class="recommendations-grid">';
    
    // Format subject recommendations
    if (recommendations.subjects) {
        html += '<div class="recommendation-section">';
        html += '<h4>Subject-Specific Recommendations</h4>';
        html += '<ul class="recommendation-list">';
        for (const subject in recommendations.subjects) {
            html += `<li>
                <strong>${subject}</strong>
                <p>${recommendations.subjects[subject]}</p>
            </li>`;
        }
        html += '</ul>';
        html += '</div>';
    }
    
    // Format learning strategies
    if (recommendations.strategies) {
        html += '<div class="recommendation-section">';
        html += '<h4>Learning Strategies</h4>';
        html += '<ul class="recommendation-list">';
        recommendations.strategies.forEach(strategy => {
            html += `<li>${strategy}</li>`;
        });
        html += '</ul>';
        html += '</div>';
    }
    
    // Format improvement areas
    if (recommendations.improvements) {
        html += '<div class="recommendation-section">';
        html += '<h4>Areas for Improvement</h4>';
        html += '<ul class="recommendation-list">';
        for (const area in recommendations.improvements) {
            html += `<li>
                <strong>${area}</strong>
                <ul>
                    ${recommendations.improvements[area].map(item => `<li>${item}</li>`).join('')}
                </ul>
            </li>`;
        }
        html += '</ul>';
        html += '</div>';
    }
    
    html += '</div>';
    return html;
} 