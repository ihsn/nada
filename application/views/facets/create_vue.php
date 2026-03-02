<!-- MDI Icons (local) -->
<link href="<?php echo base_url('javascript/mdi/css/materialdesignicons.min.css'); ?>" rel="stylesheet">

<!-- Vuetify CSS (local) -->
<link href="<?php echo base_url('javascript/vuetify.min.css'); ?>" rel="stylesheet">

<!-- Vue.js, Axios, Vuetify JS (local) -->
<script src="<?php echo base_url('javascript/vue.min.js'); ?>"></script>
<script src="<?php echo base_url('javascript/axios.min.js'); ?>"></script>
<script src="<?php echo base_url('javascript/vuetify.min.js'); ?>"></script>

<style>
    /* Contain Vuetify inside admin5 template without full-page takeover */
    #facets-app .v-application--wrap { min-height: unset; }
    #facets-app { margin-top: 8px; }
</style>

<?php require_once 'links.php'; ?>

<?php
$is_edit = isset($facet) && !empty($facet);
$page_title = $is_edit ? t('Edit Facet') : t('Create Facet');

// Build merged options — ensures every current data type has a default entry,
// even if the stored mappings predate some data types being added.
$merged_options = array();
$existing_options = array();
if (isset($facet['mappings']) && !empty($facet['mappings'])) {
    $existing_options = json_decode($facet['mappings'], true) ?: array();
}
foreach ($data_types as $type) {
    $merged_options[$type] = isset($existing_options[$type])
        ? $existing_options[$type]
        : array("field" => "", "subfield" => "", "filter" => "", "filter_value" => "");
}
?>

<div id="facets-app">
<v-app>
<v-main>
<v-container fluid>

    <h1 class="text-h5 font-weight-bold mb-6">
        <v-icon left color="primary">mdi-filter-variant</v-icon>
        <?php echo $page_title; ?>
    </h1>

    <!-- Save error alert -->
    <v-alert v-if="alert.show" :type="alert.type" dense text dismissible @input="alert.show = false" class="mb-4">
        {{ alert.message }}
    </v-alert>

    <!-- Facet details card -->
    <v-card outlined class="mb-4">
        <v-card-title class="subtitle-1 font-weight-medium pb-1">
            <v-icon left small color="grey darken-1">mdi-information-outline</v-icon>
            <?php echo t('Facet Details'); ?>
        </v-card-title>
        <v-divider></v-divider>
        <v-card-text>
            <v-row>
                <v-col cols="12" md="3">
                    <v-text-field
                        v-model="name"
                        label="<?php echo t('Name'); ?> *"
                        outlined dense
                        placeholder="A short name with no spaces"
                        hint="<?php echo t('Used as the unique identifier'); ?>"
                        persistent-hint
                    ></v-text-field>
                </v-col>
                <v-col cols="12" md="5">
                    <v-text-field
                        v-model="title"
                        label="<?php echo t('Title'); ?> *"
                        outlined dense
                        placeholder="<?php echo t('Display title'); ?>"
                    ></v-text-field>
                </v-col>
                <v-col cols="12" md="2">
                    <v-select
                        v-model="enabled"
                        label="<?php echo t('Status'); ?> *"
                        outlined dense
                        :items="[{text:'<?php echo t('Enabled'); ?>',value:'1'},{text:'<?php echo t('Disabled'); ?>',value:'0'}]"
                    ></v-select>
                </v-col>
            </v-row>
        </v-card-text>
    </v-card>

    <!-- Mappings card -->
    <v-card outlined class="mb-4">
        <v-card-title class="subtitle-1 font-weight-medium pb-1">
            <v-icon left small color="grey darken-1">mdi-map-marker-path</v-icon>
            <?php echo t('Mappings'); ?>
        </v-card-title>
        <v-divider></v-divider>
        <v-simple-table dense>
            <template v-slot:default>
                <thead>
                    <tr>
                        <th style="width:130px"><?php echo t('Data Type'); ?></th>
                        <th style="min-width:200px"><?php echo t('Field'); ?></th>
                        <th style="min-width:180px"><?php echo t('Subfield'); ?> <span class="grey--text caption">(composite types)</span></th>
                        <th style="min-width:180px"><?php echo t('Filter'); ?></th>
                        <th style="min-width:160px"><?php echo t('Filter Value'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in data_types" :key="item">

                        <td class="text-capitalize font-weight-medium">{{ item }}</td>

                        <!-- Field: typeahead + free-text via v-combobox -->
                        <td>
                            <v-combobox
                                v-model="options[item].field"
                                :items="getFieldItems(item)"
                                item-text="text"
                                item-value="value"
                                :return-object="false"
                                outlined dense hide-details clearable
                                class="my-1"
                                @change="fieldSelectionOnChange(item, $event)"
                            ></v-combobox>
                        </td>

                        <!-- Subfield: enabled for array-type fields and custom values -->
                        <td>
                            <v-combobox
                                v-model="options[item].subfield"
                                :items="getSubfieldItems(item, options[item].field)"
                                :disabled="!isSubfieldEnabled(item, options[item].field)"
                                outlined dense hide-details clearable
                                :placeholder="isSubfieldEnabled(item, options[item].field) && !getSubfieldItems(item, options[item].field).length ? 'Custom value…' : ''"
                                class="my-1"
                            ></v-combobox>
                        </td>

                        <!-- Filter field -->
                        <td>
                            <v-combobox
                                v-if="isSubfieldEnabled(item, options[item].field)"
                                v-model="options[item].filter"
                                :items="getSubfieldItems(item, options[item].field)"
                                outlined dense hide-details clearable
                                :placeholder="getSubfieldItems(item, options[item].field).length ? '' : 'Custom value…'"
                                class="my-1"
                            ></v-combobox>
                        </td>

                        <!-- Filter value -->
                        <td>
                            <v-text-field
                                v-if="isSubfieldEnabled(item, options[item].field)"
                                v-model="options[item].filter_value"
                                outlined dense hide-details
                                class="my-1"
                            ></v-text-field>
                        </td>

                    </tr>
                </tbody>
            </template>
        </v-simple-table>
    </v-card>

    <!-- Action buttons -->
    <div class="d-flex align-center">
        <v-btn color="primary" :loading="saving" @click="submitForm">
            <v-icon left>mdi-content-save</v-icon>
            <?php echo t('Save'); ?>
        </v-btn>
        <v-btn text class="ml-3" href="<?php echo site_url('admin/facets'); ?>">
            <?php echo t('Cancel'); ?>
        </v-btn>
    </div>

</v-container>
</v-main>
</v-app>
</div>

<script>
new Vue({
    el: '#facets-app',
    vuetify: new Vuetify({
        theme: {
            themes: {
                light: {
                    primary: '#1976D2'
                }
            }
        }
    }),
    data: {
        name:       <?php echo json_encode(isset($facet['name'])    ? $facet['name']    : ''); ?>,
        title:      <?php echo json_encode(isset($facet['title'])   ? $facet['title']   : ''); ?>,
        enabled:    <?php echo json_encode(isset($facet['enabled']) ? (string)$facet['enabled'] : '1'); ?>,
        data_types: <?php echo json_encode($data_types); ?>,
        fields:     <?php echo json_encode($fields); ?>,
        options:    <?php echo json_encode($merged_options); ?>,
        saving: false,
        alert: { show: false, type: 'error', message: '' }
    },
    methods: {

        // Build {text, value} items array for the field <v-select> of a given data type
        getFieldItems(data_type) {
            const fields = this.fields[data_type] || {};
            const items  = [{ text: '— Select —', value: '' }];
            for (const [key, field] of Object.entries(fields)) {
                if (!field) continue;
                if (field.items) {
                    // composite/array type — marked with *
                    items.push({ text: key + ' *', value: key });
                } else if (field.type === 'string') {
                    items.push({ text: key, value: key });
                }
            }
            return items;
        },

        // Returns the subfields properties object, or false if not applicable
        getSubfields(data_type, field_key) {
            // v-combobox may store {text,value} object if :return-object is not false — unwrap it
            if (field_key && typeof field_key === 'object') field_key = field_key.value;
            if (!field_key || typeof field_key !== 'string') return false;
            const f = (this.fields[data_type] || {})[field_key];
            if (!f || f.type !== 'array') return false;
            return (f.items && f.items.properties) || false;
        },

        // Returns subfield key names as a flat array for v-combobox suggestions
        getSubfieldItems(data_type, field_key) {
            const props = this.getSubfields(data_type, field_key);
            return props ? Object.keys(props) : [];
        },

        // Subfield is enabled when:
        //  - field is an array type (has schema subfields)
        //  - field is a custom value (not found in the schema at all)
        // Disabled when field is empty or is a known non-array schema field
        isSubfieldEnabled(data_type, field_key) {
            if (field_key && typeof field_key === 'object') field_key = field_key.value;
            if (!field_key) return false;
            const f = (this.fields[data_type] || {})[field_key];
            if (!f) return true;        // custom value not in schema — allow subfield
            return f.type === 'array';  // only array types support subfields
        },

        // When the field selection changes, auto-select the first subfield
        fieldSelectionOnChange(data_type, field_key) {
            // v-combobox may pass an object {text,value} when picking from list, or a plain string
            if (field_key && typeof field_key === 'object') field_key = field_key.value;
            if (!field_key) { this.options[data_type].subfield = ''; return; }
            const f = (this.fields[data_type] || {})[field_key];
            if (f && f.type === 'array') {
                try {
                    const keys = Object.keys(f.items.properties);
                    this.options[data_type].subfield = keys.length ? keys[0] : '';
                } catch (e) {
                    this.options[data_type].subfield = '';
                }
            } else {
                this.options[data_type].subfield = '';
            }
        },

        submitForm() {
            this.saving = true;
            const url   = '<?php echo site_url('api/facets'); ?>';
            const data  = {
                title:      this.title,
                name:       this.name,
                facet_type: 'user',
                enabled:    this.enabled,
                mappings:   this.options,
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            };

            axios.post(url, data)
                .then(() => {
                    window.location.replace('<?php echo site_url('admin/facets'); ?>');
                })
                .catch(err => {
                    this.saving = false;
                    const msg = err.response && err.response.data && err.response.data.message
                        ? err.response.data.message
                        : '<?php echo t('Save failed. Please try again.'); ?>';
                    this.alert = { show: true, type: 'error', message: msg };
                });
        }

    }
});
</script>
