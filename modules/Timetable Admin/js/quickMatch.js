(function () {
    function init() {
        var dataElement = document.getElementById('quickMatchData');
        var textarea = document.getElementById('matchTextArea');
        var button = document.getElementById('matchTextBtn');
        var resultElement = document.getElementById('matchTextResult');
        var source = document.getElementById('MembersSource');
        var destination = document.getElementById('Members');
        var addButton = document.getElementById('MembersAdd');

        if (!dataElement || !textarea || !button || !source || !destination) return;

        var data;
        try {
            data = JSON.parse(dataElement.textContent);
        } catch (e) {
            return;
        }

        var students = Array.isArray(data.students) ? data.students : [];
        var labels = data.labels || {};
        var normalize = function (value) {
            return String(value || '').trim().toLowerCase().replace(/\s+/g, ' ');
        };
        var findMatches = function (query) {
            var value = normalize(query);
            if (value === '') return [];

            var exact = students.filter(function (student) {
                return normalize(student.username) === value || normalize(student.name) === value;
            });
            if (exact.length > 0) return exact;

            return students.filter(function (student) {
                return normalize(student.search).indexOf(value) !== -1;
            });
        };
        var selectStudent = function (student) {
            if (destination.querySelector('option[value="' + student.id + '"]')) {
                return true;
            }

            var option = source.querySelector('option[value="' + student.id + '"]');
            if (!option) return false;

            option.selected = true;
            return true;
        };
        var setResult = function (message, hasIssues) {
            if (!resultElement) return;
            resultElement.textContent = message;
            resultElement.className = hasIssues ? 'text-sm text-red-600' : 'text-sm text-green-700';
        };

        button.addEventListener('click', function () {
            if (students.length === 0) {
                setResult(labels.noStudents || '', true);
                return;
            }

            Array.prototype.forEach.call(source.options, function (option) {
                option.selected = false;
            });

            var selectedIds = {};
            var notFound = [];
            var multiple = [];

            textarea.value.split(/\r?\n/).forEach(function (line) {
                var query = normalize(line.replace(/^\uFEFF/, ''));
                if (query === '') return;

                var matches = findMatches(query);
                if (matches.length === 0) {
                    notFound.push(line.trim());
                    return;
                }
                if (matches.length > 1) {
                    multiple.push(line.trim());
                }

                matches.forEach(function (student) {
                    if (selectStudent(student)) selectedIds[student.id] = true;
                });
            });

            var selected = Object.keys(selectedIds).length;
            if (selected > 0 && addButton) {
                addButton.click();
            }

            var messages = [(labels.selected || 'Selected') + ': ' + selected];
            if (notFound.length > 0) {
                messages.push((labels.notFound || 'Not found') + ': ' + notFound.join(', '));
            }
            if (multiple.length > 0) {
                messages.push((labels.multiple || 'Multiple matches') + ': ' + multiple.join(', '));
            }
            setResult(messages.join(' | '), notFound.length > 0 || multiple.length > 0);
        });

        textarea.addEventListener('keydown', function (event) {
            if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                event.preventDefault();
                button.click();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
