/**
 * Function to empty page in case the server-side response is not valid
 */
function emptyPage() {
    const parametersDiv = document.getElementById('parameters');
    const parametersBlocks = parametersDiv.getElementsByClassName('parameters-block');

    if (parametersBlocks.length > 1) {
        parametersBlocks[1].remove();
    }

    if (parametersBlocks.length > 0) {
        const div = parametersBlocks[0];
        div.innerHTML = '';
        const h2 = document.createElement('h2');
        h2.textContent = 'API service not available, please contact an administrator';
        div.appendChild(h2);
    }
}

/**
 * Function to update activity keywords blocks IDs
 */
function updateActivityKeywordsIDs() {
    const elements = document.querySelectorAll('div[id^="prm-activity-keyword-"]');
    elements.forEach((el, index) => {
        const newIndex = index + 1;
        el.id = `prm-activity-keyword-${newIndex}`;

        const removeBtn = el.querySelector('.remove-button');
        if (removeBtn) {
            removeBtn.id = `remove-${newIndex}`;
        }

        const ruleSelect = el.querySelector('.activity-keyword-rule');
        if (ruleSelect) {
            ruleSelect.name = `activity-keyword-rule-${newIndex}`;
        }

        const valueInput = el.querySelector('.activity-keyword-value');
        if (valueInput) {
            valueInput.name = `activity-keyword-value-${newIndex}`;
        }
    });
}

/**
 * Function to update reference flow keywords blocks IDs
 */
function updateReferenceFlowKeywordsIDs() {
    const elements = document.querySelectorAll('div[id^="prm-reference-flow-keyword-"]');
    elements.forEach((el, index) => {
        const newIndex = index + 1;
        el.id = `prm-reference-flow-keyword-${newIndex}`;

        const removeBtn = el.querySelector('.remove-button');
        if (removeBtn) {
            removeBtn.id = `remove-${newIndex}`;
        }

        const ruleSelect = el.querySelector('.reference-flow-keyword-rule');
        if (ruleSelect) {
            ruleSelect.name = `reference-flow-keyword-rule-${newIndex}`;
        }

        const valueInput = el.querySelector('.reference-flow-keyword-value');
        if (valueInput) {
            valueInput.name = `reference-flow-keyword-value-${newIndex}`;
        }
    });
}

/**
 * Function to update intermediate exchange keywords blocks IDs
 */
function updateIntermediateExchangeKeywordsIDs() {
    const elements = document.querySelectorAll('div[id^="prm-intermediate-exchange-keyword-"]');
    elements.forEach((el, index) => {
        const newIndex = index + 1;
        el.id = `prm-intermediate-exchange-keyword-${newIndex}`;

        const removeBtn = el.querySelector('.remove-button');
        if (removeBtn) {
            removeBtn.id = `remove-${newIndex}`;
        }

        const ruleSelect = el.querySelector('.intermediate-exchange-keyword-rule');
        if (ruleSelect) {
            ruleSelect.name = `intermediate-exchange-keyword-rule-${newIndex}`;
        }

        const valueInput = el.querySelector('.intermediate-exchange-keyword-value');
        if (valueInput) {
            valueInput.name = `intermediate-exchange-keyword-value-${newIndex}`;
        }
    });
}

/**
 * Function to update impact categories blocks IDs
 */
function updateImpactCategoriesIDs() {
    const elements = document.querySelectorAll('div[id^="prm-impact-category-"]');
    elements.forEach((el, index) => {
        const newIndex = index + 1;
        el.id = `prm-impact-category-${newIndex}`;

        const removeBtn = el.querySelector('.remove-button');
        if (removeBtn) {
            removeBtn.id = `remove-${newIndex}`;
        }

        const packageSelect = el.querySelector('.impact-category-package');
        if (packageSelect) {
            packageSelect.name = `impact-category-package-${newIndex}`;
        }

        const ruleSelect = el.querySelector('.impact-category-rule');
        if (ruleSelect) {
            ruleSelect.name = `impact-category-rule-${newIndex}`;
        }

        const valueInput = el.querySelector('.impact-category-value');
        if (valueInput) {
            valueInput.name = `impact-category-value-${newIndex}`;
        }
    });
}

/**
 * Function to update elementary flows blocks IDs
 */
function updateElementaryFlowsIDs() {
    const elements = document.querySelectorAll('div[id^="prm-elementary-flow-"]');
    elements.forEach((el, index) => {
        const newIndex = index + 1;
        el.id = `prm-elementary-flow-${newIndex}`;

        const removeBtn = el.querySelector('.remove-button');
        if (removeBtn) {
            removeBtn.id = `remove-${newIndex}`;
        }

        const ruleSelect = el.querySelector('.elementary-flow-rule');
        if (ruleSelect) {
            ruleSelect.name = `elementary-flow-rule-${newIndex}`;
        }

        const valueInput = el.querySelector('.elementary-flow-value');
        if (valueInput) {
            valueInput.name = `elementary-flow-value-${newIndex}`;
        }
    });
}

/**
 * Function to populate impact categories select
 */
function populateImpactCategories(selectId) {
    try {
        const storedData = sessionStorage.getItem('impactCategories');

        if (!storedData) {
            throw new Error('No impactCategories data found in session storage');
        }
        const impactCategories = JSON.parse(storedData);

        const parentElement = document.getElementById(selectId);
        if (!parentElement) {
            throw new Error(`Parent element with id "${selectId}" not found`);
        }

        // Find the select element with class "select-2" inside the parent element
        const selectElement = parentElement.querySelector('.select-2');
        if (!selectElement) {
            throw new Error(`Select element with class "select-2" not found inside parent element with id "${selectId}"`);
        }

        // Clear existing options
        selectElement.innerHTML = '';

        // Add default option to select-2
        const defaultOption = document.createElement('option');
        defaultOption.value = '0';
        defaultOption.textContent = '-';
        selectElement.appendChild(defaultOption);

        // Populate select with options based on object keys
        Object.keys(impactCategories).forEach(key => {
            const option = document.createElement('option');
            option.value = key;
            option.textContent = key;
            selectElement.appendChild(option);
        });
    } catch (error) {
        console.error('Error populating impact categories:', error);
        popToast('Error populating impact categories');
    }
}

/**
 * Function to fetch impact categories
 */
async function fetchImpactCategories(selectedValue) {
    try {
        const url = 'api.php?request=get-indicators&source=' + encodeURIComponent(selectedValue);
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error('Failed to fetch data');
        }
        const data = await response.json();
        sessionStorage.setItem('impactCategories', JSON.stringify(data.data));
    } catch (error) {
        console.error('Error fetching or storing data:', error);
        popToast('Error fetching or storing data');
    }
}

/**
 * Function to fetch impact categories
 */
async function fetchElementaryFlows(selectedValue) {
    try {
        const url = 'api.php?request=get-elementary-flows&source=' + encodeURIComponent(selectedValue);
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error('Failed to fetch data');
        }
        const data = await response.json();
        sessionStorage.setItem('elementaryFlows', JSON.stringify(data.data));
    } catch (error) {
        console.error('Error fetching or storing data:', error);
    }
}

/**
 * Function to populate indicators
 */
function populateIndicators(parentId) {
    try {
        // Find the parent element by parentId
        const parentElement = document.getElementById(parentId);
        if (!parentElement) {
            throw new Error(`Parent element with id "${parentId}" not found`);
        }

        // Get the selected value from .impact-category within the parent element
        const impactCategory = parentElement.querySelector('.impact-category');
        if (!impactCategory) {
            throw new Error(`Element with class "impact-category" not found inside parent element with id "${parentId}"`);
        }
        const selectedValue = impactCategory.value;

        // Retrieve impactCategories from session storage
        const storedData = sessionStorage.getItem('impactCategories');
        if (!storedData) {
            throw new Error('No impactCategories data found in session storage');
        }

        // Parse impactCategories into an object
        const impactCategories = JSON.parse(storedData);

        // Retrieve the list of names corresponding to selectedValue from impactCategories
        const names = impactCategories[selectedValue];
        if (!names) {
            throw new Error(`No names found for key "${selectedValue}" in impactCategories`);
        }

        // Find .indicator within the parent element and clear existing options
        const indicator = parentElement.querySelector('.indicator');
        if (!indicator) {
            throw new Error(`Element with class "indicator" not found inside parent element with id "${parentId}"`);
        }
        indicator.innerHTML = '';

        // Populate .indicator with options based on the names array
        names.forEach(name => {
            const option = document.createElement('option');
            option.value = name;
            option.textContent = name;
            indicator.appendChild(option);
        });

    } catch (error) {
        console.error('Error populating indicator:', error);
        popToast('Error populating indicator');
    }
}

/**
 * Function to populate elementary flows
 */
function populateElementaryFlows() {
    // Retrieve elementaryFlows from session storage
    const storedData = sessionStorage.getItem('elementaryFlows');
    if (!storedData) {
        console.log('No elementaryFlows data found in session storage');
        return;
    }

    const elementaryFlows = JSON.parse(storedData);

    // Select all inputs with class '.autocomplete' that have not been initialized yet
    document.querySelectorAll('.autocomplete:not([data-autocomplete-initialized])').forEach(input => {
        new autoComplete({
            data: {
                src: elementaryFlows
            },
            selector: () => input,
            resultItem: {
                highlight: true,
            },
            events: {
                input: {
                    selection: (event) => {
                        const selection = event.detail.selection.value;
                        input.value = selection;
                    }
                }
            }
        });

        // Add a marker attribute to indicate that this input has been initialized
        input.setAttribute('data-autocomplete-initialized', true);
    });
}

/**
 * Function to pop toast
 */
function popToast(message) {
    const toast = document.createElement('div');
    toast.classList.add('toast');

    const messageSpan = document.createElement('span');
    messageSpan.textContent = message;

    toast.innerHTML = `<svg width="25px" height="25px" stroke-width="1.7" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="#ffffff"><path d="M12 11.5V16.5" stroke="#ffffff" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path><path d="M12 7.51L12.01 7.49889" stroke="#ffffff" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path><path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="#ffffff" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path></svg><span>${message}</span>`;

    document.body.appendChild(toast);

    /*
    setTimeout(function() {
        toast.remove();
    }, 6000);

    */
}