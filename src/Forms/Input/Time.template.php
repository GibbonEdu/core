<div class="flex-grow relative flex text-left" 
    x-data="{
        clock: <?= $clock ?>,
        time: '<?= $value ?>',
        timeSelected: null,
        timeOptions: { hour: '<?= $clock == '12' ? 'numeric' : '2-digit' ?>', minute: '2-digit', hour12: <?= $clock == '12' ? 'true' : 'false' ?> },
        chainedTo: null,
        chainedFrom: null,
        isOpen: false, 
        selectedItem: '',
        timePickerView: 1,

        maskTime(event) {
            event.target.value = event.target.value.replace(/[^0-9: apm]/g, '');
            time = event.target.value;
        },

        formatTime(event) {
            let x = event.target.value.replace(/[^0-9: apm]/g, '').match(/^(\d{0,2}):?(\d{0,2})\s?(.*)/);
            event.target.value = !x[1] && !x[2]
                ? ''
                : String(!x[1] ? '0' : x[1]).padStart(this.clock == 12 ? 1 : 2, '0') + ':' + String(!x[2] ? '00' : x[2]).padStart(2, '0') + (!x[3] || this.clock == 24 ? '' : ' '+x[3]);

            this.$refs.hiddenInput.dispatchEvent(new Event('blur', { bubbles: true }));
        },

        convertToMinutes(timeString) {
            timeString = String(timeString);

            var hours = Number(timeString.match(/^(\d+)/)?.[1]);
            var minutes = Number(timeString.match(/:(\d+)/)?.[1] ?? 0);
            var period = timeString.match(/[:\d]?\s?([am|pm|AM|PM]{0,2})$/)?.[1];

            if (period?.toLowerCase() === 'pm') {
                hours += 12;
            }
            return (hours * 60) + minutes;
        },

        convertTo12HourFormat(timeString) {
            let date = timeString ? new Date('2000-01-01T' + timeString) : null;
            return date ? date.toLocaleTimeString('en-GB', this.timeOptions).toLowerCase() : null;
        },

        convertTo24HourFormat(timeString) {
            timeString = String(timeString);

            var hours = Number(timeString.match(/^(\d+):/)?.[1]);
            var minutes = Number(timeString.match(/:(\d+)/)?.[1] ?? 0);
            var period = timeString.match(/[:\d]?\s?([am|pm|AM|PM]{0,2})$/)?.[1];

            if (hours == null || isNaN(hours)) return null;

            if (period != null && hours <= 12) {
                if (period.toLowerCase() === 'pm' && hours !== 12) {
                    hours += 12; // Add 12 for PM hours (except 12 PM, which remains 12)
                } else if (period.toLowerCase() === 'am' && hours === 12) {
                    hours = 0; // Convert 12 AM to 0 (midnight)
                }
            }

            // Ensure hours and minutes are two digits with leading zeros if necessary
            const formattedHours = String(hours).padStart(2, '0');
            const formattedMinutes = String(minutes).padStart(2, '0');

            return `${formattedHours}:${formattedMinutes}`;
        },
        availableTimes: [],
        unAvailableTimes: [], 
        setupTimePicker() {
            this.time = this.clock == '12' ? this.convertTo12HourFormat(this.time) : this.convertTo24HourFormat(this.time);
            this.chainedTo = '<?= $chainedTo ?>' != '' ? document.getElementById('<?= $chainedTo ?>') : null;
            this.chainedFrom = '<?= $chainedFrom ?>' != '' ? document.getElementById('<?= $chainedFrom ?>') : null;
            this.setAvailableTimes();
        },

        setAvailableTimes() {
            this.availableTimes = [];
            let currentTime = this.convertToMinutes(this.time ?? '<?= date('H:i') ?>');
            let start = this.convertToMinutes('<?= $minimum ?>'); 
            let end = this.convertToMinutes('<?= $maximum ?>');

            for (let minutes = start; minutes <= end; minutes += 15) {
                let hours = String(Math.floor(minutes / 60)).padStart(2, '0');
                let mins = String(minutes % 60).padStart(2, '0');
                let time = `${hours}:${mins}`;

                let itemTime = this.convertToMinutes(time);
                let chainedFromTime = this.convertToMinutes(this.chainedFrom?.value ?? '');
                let relativeTime = itemTime - currentTime;
                let label = '';

                if (relativeTime >= -15 && relativeTime <= 0) {
                    this.timeSelected = this.availableTimes.length;
                }

                if (this.chainedFrom && itemTime < chainedFromTime ) {
                    continue;
                }

                if (this.chainedFrom && itemTime - chainedFromTime >= 0) {

                    const rtf = new Intl.RelativeTimeFormat(navigator.language, { numeric: 'always' });
                    let diff = itemTime - chainedFromTime;
                    label = ' (' + rtf.format(diff >= 60 ? ((itemTime - chainedFromTime) / 60).toFixed(2) : (itemTime - chainedFromTime), diff >= 60 ? 'hour' : 'minute').replace('in ', '') +')';
                }

                if (this.clock == '12') {
                    time = this.convertTo12HourFormat(time);
                }
                
                if (!this.unAvailableTimes.includes(time)) {
                    this.availableTimes.push({time: time, label: label});
                }
            }
        },

        setTime(time) { 
            this.time = this.clock == '12' ? this.convertTo12HourFormat(time) : this.convertTo24HourFormat(time);
            this.timeSelected = this.availableTimes.find( (item) => item.time == this.time);

            this.$refs.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        },

        selectTime(index, time, nextTime) {
            this.time = time; 
            this.timeSelected = index; 
            this.isOpen = false;

            this.$refs.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));

            if (this.chainedTo) {
                this.chainedTo.value = nextTime ? nextTime : this.time;
                this.chainedTo.dispatchEvent(new Event('input', { bubbles: true }));
            }
        },

        updateActiveItem() {
            let currentTime = this.convertToMinutes(this.time);

            for (let i = 0; i < this.availableTimes.length; i++) {
                let relativeTime = this.convertToMinutes(this.availableTimes[i]);
                
                if (currentTime - relativeTime >= 0 && currentTime - relativeTime <= 15) {
                    this.timeSelected = i;
                }
            }

            this.$refs.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));

            this.scrollToActiveItem();
        },

        scrollToActiveItem(){
            if(this.timeSelected){
                activeElement = document.getElementById(this.$refs.hiddenInput.id+'Option-' + this.timeSelected);
                newScrollPos = (activeElement.offsetTop + activeElement.offsetHeight) - ((this.$refs.timeList.offsetHeight ) / 2);
                this.$refs.timeList.scrollTop=newScrollPos > 0 ? newScrollPos : 0;
            }
        },
    }"
    x-on:keydown.esc.window="isOpen=false"
    x-init="setupTimePicker()"
    x-trap="isOpen"
    x-on:click.outside="isOpen=false" 
    >

    <span class="pointer-events-none absolute top-0.5 right-2">
        <?= icon('outline', 'clock', 'pointer-events-none size-8 mt-px p-1.5 rounded text-gray-600 hover:text-gray-800'); ?>
    </span>

    <input x-cloak type="time" <?= $attributes; ?> maxlength="5"
        class="hidden invisible"
        value="<?= $value; ?>" 
        :value="convertTo24HourFormat(time)"
        x-ref="hiddenInput"
    />
    <!-- @input="setTime($el.value)" -->

    <input type="text" :id="$refs.hiddenInput.id + 'Time'" :name="$refs.hiddenInput.id + 'Time'" 
        @click="isOpen=true; setAvailableTimes(); $focus.focus($refs.timePicker); $nextTick(() => scrollToActiveItem() )"
        @input="updateActiveItem()"
        @blur="formatTime"
        x-model="time"
        x-ref="timePicker"
        placeholder="--:--"
        value="<?= $value ? date($clock == '12' ? 'g:i a' : 'H:i', strtotime($value)) : ''; ?>" 
        class="<?= $class; ?> <?= $groupClass; ?> inner-input w-full min-w-0 py-2 font-sans placeholder:text-gray-500  sm:text-sm sm:leading-6 <?= $type != 'text' ? 'input-icon' : ''; ?>
        <?= !empty($readonly) ? 'border-dashed text-gray-600 cursor-not-allowed focus:ring-0 focus:border-gray-400' : 'text-gray-900 focus:ring-1 focus:ring-inset focus:ring-blue-500'; ?>
    "/>

    

    <div x-cloak x-show="isOpen" :id="$refs.hiddenInput.id+'List'"
        class="absolute mt-10 top-0 left-0 z-50 w-full max-w-64 h-60 rounded-md border bg-white shadow-lg" 
        x-on:keydown.down.prevent="$focus.wrap().next()" 
        x-on:keydown.up.prevent="$focus.wrap().previous()" 
        x-transition:enter.opacity.duration.100ms 
        x-transition:leave.opacity.duration.0ms 
        style="display:none;"
        >

        <nav class="flex justify-around bg-gray-200 py-1 border-b rounded-t-md">

            <button @click="timePickerView=1" type="button" class="px-4 py-1 text-center text-xxs hover:bg-gray-400 text-gray-600 rounded-md" 
                :class="{'bg-gray-400 text-gray-800' : timePickerView==1}">
                <?= __('Time') ?>
            </button>

            <button @click="timePickerView=2" type="button" class="bg-gray-200 px-4 py-1 text-center text-xxs hover:bg-gray-400 text-gray-600 rounded-md" 
                hx-post="<?= $absoluteURL ?>/modules/User/form_time_ajax.php" 
                x-bind:hx-target="'#'+$refs.hiddenInput.id+'PeriodList'" 
                hx-include="<?= !empty($date) ? '#'.$date : '' ?>" 
                hx-vals='{"key": "<?= $date ?>"}'
                :class="{'bg-gray-400 text-gray-800' : timePickerView==2}">
                <?= __('Period') ?>
            </button>

        </nav>

        <div x-show="timePickerView==1" class="absolute w-full h-full" role="listbox" aria-label="list">

            <ul class="flex max-h-52 flex-col overflow-y-auto overflow-x-hidden m-0 p-1 rounded-b-md" x-ref="timeList">
        
            <template x-for="(value, index) in availableTimes" :key="index" >
                <li x-from-template :value="time" role="option" tabindex="0" 
                x-bind:id="$refs.hiddenInput.id+'Option-' + index"
                x-on:click="selectTime(index, value.time, null)"
                x-on:keydown.enter="selectTime(index, value.time, null)"
                :class="timeSelected == index ? 'bg-gray-300 text-gray-900 hover:text-white' : ''"
                class="px-3 py-1 text-sm text-gray-700 rounded hover:bg-blue-500 hover:text-white focus:bg-blue-500 focus:text-white cursor-pointer whitespace-nowrap"
                >
                <span x-text="value.time"></span>
                <span x-text="value.label" class="ml-1 text-xs text-gray-500"></span>
                </li>
            </template>

            </ul>

        </div>

        <div x-cloak x-show="timePickerView==2" class="absolute w-full h-full" role="listbox" aria-label="list">
            <ul :id="$refs.hiddenInput.id+'PeriodList'" class="flex max-h-52 flex-col overflow-y-auto overflow-x-hidden m-0 p-1 rounded-b-md">
            </ul>
        </div>

    </div>

</div>
