import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import publicationMap from './components/publication-map';
import locationPicker from './components/location-picker';
import communePicker from './components/commune-picker';

Alpine.data('publicationMap', publicationMap);
Alpine.data('locationPicker', locationPicker);
Alpine.data('communePicker', communePicker);

Livewire.start();
