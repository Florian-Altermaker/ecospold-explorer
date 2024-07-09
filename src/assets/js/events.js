/**
 * Load available sources
 */
document.addEventListener('DOMContentLoaded', function() {
    fetch('api.php?request=get-sources')
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const selectElement = document.getElementById('prm-source');
            data.data.forEach(source => {
                const option = document.createElement('option');
                option.value = source.id;
                option.textContent = source.name;
                selectElement.appendChild(option);
            });
        } else {
            emptyPage();
            console.error('Failed to fetch sources:', data);
            popToast('Failed to fetch sources');
        }
    })
    .catch(error => {
        emptyPage();
        console.error('Error fetching sources:', error);
        popToast('Error fetching sources');
    });
});

/**
 * Fetch available indicators and elementary flows when the selected source changes
 */
document.getElementById('prm-source').addEventListener('change', function(event) {
    const selectedValue = event.target.value;
    fetchImpactCategories(selectedValue);
    fetchElementaryFlows(selectedValue);
    const hiddenElements = document.querySelectorAll('.hidden');
        hiddenElements.forEach(element => {
        element.style.display = 'block';
    });
});

/**
 * Button to add an activity keyword on click
 */
document.getElementById("add-activity-keyword").addEventListener('click', function() {
    const parentDiv = document.getElementById('activity-keywords');
    const children = parentDiv.querySelectorAll('div[id^="prm-activity-keyword-"]');
    const newIdNumber = children.length + 1;
    const newKeyword = document.createElement('div');
    newKeyword.id = `prm-activity-keyword-${newIdNumber}`;
    newKeyword.classList.add('select-text');
    newKeyword.classList.add('form-input');
    newKeyword.innerHTML = '<select class="select-1 activity-keyword-rule" name="activity-keyword-rule-'+newIdNumber+'"><option value="match" selected>match</option><option value="begin">begin with</option><option value="end">end with</option></select><input type="text" class="activity-keyword-value" name="activity-keyword-value-'+newIdNumber+'"><svg class="remove-button" width="30px" height="30px" stroke-width="1.7" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="#800080"><path d="M6.75827 17.2426L12.0009 12M17.2435 6.75736L12.0009 12M12.0009 12L6.75827 6.75736M12.0009 12L17.2435 17.2426" stroke="#800080" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
    parentDiv.appendChild(newKeyword);
});

/**
 * Button to remove an activity keyword
 */
document.getElementById('activity-keywords').addEventListener('click', function(e) {
    if (e.target && e.target.closest('.remove-button')) {
        const parentDiv = e.target.closest('.remove-button').parentElement;
        parentDiv.remove();
        updateActivityKeywordsIDs();
    }
});

/**
 * Button to add a reference flow keyword on click
 */
document.getElementById("add-reference-flow-keyword").addEventListener('click', function() {
    const parentDiv = document.getElementById('reference-flow-keywords');
    const children = parentDiv.querySelectorAll('div[id^="prm-reference-flow-keyword-"]');
    const newIdNumber = children.length + 1;
    const newKeyword = document.createElement('div');
    newKeyword.id = `prm-reference-flow-keyword-${newIdNumber}`;
    newKeyword.classList.add('select-text');
    newKeyword.classList.add('form-input');
    newKeyword.innerHTML = '<select class="select-1 reference-flow-keyword-rule" name="reference-flow-keyword-rule-'+newIdNumber+'"><option value="match" selected>match</option><option value="begin">begin with</option><option value="end">end with</option></select><input type="text" class="reference-flow-keyword-value" name="reference-flow-keyword-value-'+newIdNumber+'"><svg class="remove-button" width="30px" height="30px" stroke-width="1.7" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="#800080"><path d="M6.75827 17.2426L12.0009 12M17.2435 6.75736L12.0009 12M12.0009 12L6.75827 6.75736M12.0009 12L17.2435 17.2426" stroke="#800080" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
    parentDiv.appendChild(newKeyword);
});

/**
 * Button to remove a reference flow keyword
 */
document.getElementById('reference-flow-keywords').addEventListener('click', function(e) {
    if (e.target && e.target.closest('.remove-button')) {
        const parentDiv = e.target.closest('.remove-button').parentElement;
        parentDiv.remove();
        updateReferenceFlowKeywordsIDs();
    }
});

/**
 * Button to add an intermediate exchange keyword on click
 */
document.getElementById("add-intermediate-exchange-keyword").addEventListener('click', function() {
    const parentDiv = document.getElementById('intermediate-exchange-keywords');
    const children = parentDiv.querySelectorAll('div[id^="prm-intermediate-exchange-keyword-"]');
    const newIdNumber = children.length + 1;
    const newKeyword = document.createElement('div');
    newKeyword.id = `prm-intermediate-exchange-keyword-${newIdNumber}`;
    newKeyword.classList.add('select-text');
    newKeyword.classList.add('form-input');
    newKeyword.innerHTML = '<select class="select-1 intermediate-exchange-keyword-rule" name="intermediate-exchange-keyword-rule-'+newIdNumber+'"><option value="match" selected>match</option><option value="begin">begin with</option><option value="end">end with</option></select><input type="text" class="intermediate-exchange-keyword-value" name="intermediate-exchange-keyword-value-'+newIdNumber+'"><svg class="remove-button" width="30px" height="30px" stroke-width="1.7" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="#800080"><path d="M6.75827 17.2426L12.0009 12M17.2435 6.75736L12.0009 12M12.0009 12L6.75827 6.75736M12.0009 12L17.2435 17.2426" stroke="#800080" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
    parentDiv.appendChild(newKeyword);
});

/**
 * Button to remove an intermediate exchange keyword
 */
document.getElementById('intermediate-exchange-keywords').addEventListener('click', function(e) {
    if (e.target && e.target.closest('.remove-button')) {
        const parentDiv = e.target.closest('.remove-button').parentElement;
        parentDiv.remove();
        updateIntermediateExchangeKeywordsIDs();
    }
});

/**
 * Button to add an impact category on click
 */
document.getElementById("add-impact-category").addEventListener('click', function() {
    const parentDiv = document.getElementById('impact-categories');
    const children = parentDiv.querySelectorAll('div[id^="prm-impact-category-"]');
    const newIdNumber = children.length + 1;
    const newKeyword = document.createElement('div');
    newKeyword.id = `prm-impact-category-${newIdNumber}`;
    newKeyword.classList.add('select-select-select');
    newKeyword.classList.add('form-input');
    newKeyword.innerHTML = '<select class="select-1 impact-category-rule" name="impact-category-rule-'+newIdNumber+'"><option value="total" selected>total</option><option value="detail">detail</option></select><select class="select-2 impact-category impact-category-package" name="impact-category-package-'+newIdNumber+'"><option value="0">-</option></select><select class="select-3 indicator impact-category-value" name="impact-category-value-'+newIdNumber+'"><option value="0">-</option></select><svg class="remove-button" width="30px" height="30px" stroke-width="1.7" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="#800080"><path d="M6.75827 17.2426L12.0009 12M17.2435 6.75736L12.0009 12M12.0009 12L6.75827 6.75736M12.0009 12L17.2435 17.2426" stroke="#800080" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
    parentDiv.appendChild(newKeyword);
    populateImpactCategories(`prm-impact-category-${newIdNumber}`);
});

/**
 * Button to remove an impact category
 */
document.getElementById('impact-categories').addEventListener('click', function(e) {
    if (e.target && e.target.closest('.remove-button')) {
        const parentDiv = e.target.closest('.remove-button').parentElement;
        parentDiv.remove();
        updateImpactCategoriesIDs();
    }
});

/**
 * Button to add an elementary flow
 */
document.getElementById("add-elementary-flow").addEventListener('click', function() {
    const parentDiv = document.getElementById('elementary-flows');
    const children = parentDiv.querySelectorAll('div[id^="prm-elementary-flow-"]');
    const newIdNumber = children.length + 1;
    const newKeyword = document.createElement('div');
    newKeyword.id = `prm-elementary-flow-${newIdNumber}`;
    newKeyword.classList.add('select-text');
    newKeyword.classList.add('form-input');
    newKeyword.innerHTML = '<select class="select-1 elementary-flow-rule" name="elementary-flow-rule-'+newIdNumber+'"><option value="total" selected>total</option><option value="detail">detail</option></select><input type="text" class="autocomplete elementary-flow-value" name="elementary-flow-value-'+newIdNumber+'"><svg class="remove-button" width="30px" height="30px" stroke-width="1.7" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="#800080"><path d="M6.75827 17.2426L12.0009 12M17.2435 6.75736L12.0009 12M12.0009 12L6.75827 6.75736M12.0009 12L17.2435 17.2426" stroke="#800080" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
    parentDiv.appendChild(newKeyword);
    populateElementaryFlows(`prm-elementary-flow-${newIdNumber}`);
});

/**
 * Button to remove an elementary flow
 */
document.getElementById('elementary-flows').addEventListener('click', function(e) {
    if (e.target && e.target.closest('.remove-button')) {
        const parentDiv = e.target.closest('.remove-button').parentElement;
        parentDiv.remove();
        updateElementaryFlowsIDs();
    }
});

/**
 * Event to populate indicators
 */
document.addEventListener('change', function(event) {
    // Check if the changed element matches .impact-category
    if (event.target && event.target.classList.contains('impact-category')) {
        const parentElement = event.target.closest('[id]');
        if (parentElement) {
            const parentId = parentElement.id;
            populateIndicators(parentId);
        } else {
            console.error('Parent element with an ID not found');
            popToast('Parent element with an ID not found');
        }
    }
});

/**
 * Event to check intermediate exchanges checkbox on input in corresponding keyword
 */
const parentDiv = document.getElementById('intermediate-exchange-keywords');
const intermediateExchangeCheckbox = document.getElementById('output-intermediate-exchanges');

parentDiv.addEventListener('input', function(event) {
    const target = event.target;
    if (target.matches('#intermediate-exchange-keywords [id^="prm-intermediate-exchange-"] input[type="text"]')) {
        if (target.value.trim().length > 0) {
            intermediateExchangeCheckbox.checked = true;
        } else {
            intermediateExchangeCheckbox.checked = false;
        }
    }
});

/**
 * Download button
 */
document.getElementById("dl-button").addEventListener('click', function() {

    // Hide DL button
    document.getElementById('dl-button').style.display = 'none';
    document.getElementById('dl-go').style.display = 'none';
    document.getElementById('dl-loader').style.display = 'block';

    // Retrieve select values
    const selectValues = Array.from(document.querySelectorAll('select')).map(select => {
        return {name: select.name, value: select.value };
    });
    //console.log('Select values:', selectValues);

    // Retrieve input value
    const inputValues = Array.from(document.querySelectorAll('input')).map(input => {
        if (input.type === 'checkbox') {
            return { name: input.name, value: input.checked };
        } else if (input.type === 'radio') {
            if (input.checked) {
                return { name: input.name, value: input.value };
            }
        } else {
            return { name: input.name, value: input.value };
        }
    }).filter(value => value !== undefined);

    //console.log('Input values:', inputValues);

    const params = {
        selects: selectValues,
        inputs: inputValues
    };

    requestExtract(params);

});

/**
 * Fetch function to send all download information to api
 */
function requestExtract(params)
{
    const queryString = Object.keys(params)
    .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(JSON.stringify(params[key]))}`)
    .join('&');

    // URL with query string
    const url = `api.php?request=get-extract&${queryString}`;

    // Fetch API using GET method with body parameters in query string
    fetch(url, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        //console.log(response);
        return response.json();
    })
    .then(data => {
        const goButton = document.getElementById('dl-go');
        goButton.href = data.data["href"];
        //console.log('API response:', data);
        document.getElementById('dl-loader').style.display = 'none';
        document.getElementById('dl-button').style.display = 'block';
        document.getElementById('dl-go').style.display = 'block';
    })
    .catch(error => {
        console.error('Error fetching API:', error);
        popToast('Error fetching API');
        document.getElementById('dl-loader').style.display = 'none';
        document.getElementById('dl-button').style.display = 'block';
        document.getElementById('dl-go').style.display = 'none';
    });
}