<?php
/**
* Custom fields rendering for public request forms
*
*/
?>

<?php if (isset($custom_fields) && !empty($custom_fields)): ?>
    <div class="row">
    <?php foreach ($custom_fields as $field_key => $field_config): ?>
        <div class="col-md-12 mb-3">
            <label for="<?php echo $field_name; ?>" class="form-label font-weight-bold">
                <?php echo $field_config['title']; ?>
                <?php if (isset($field_config['required']) && $field_config['required']): ?>
                    <span class="text-danger">*</span>
                <?php endif; ?>
            </label>
            <div class="form-group">
                <?php 
                // Use field name from config, fallback to field_key if not set
                $field_name = isset($field_config['name']) ? $field_config['name'] : $field_key;
                ?>
                <?php if ($field_config['type'] == 'textarea'): ?>
                    <textarea 
                        id="<?php echo $field_name; ?>"
                        name="<?php echo $field_name; ?>" 
                        class="form-control" 
                        rows="4"
                        <?php echo (isset($field_config['placeholder'])) ? 'placeholder="'.$field_config['placeholder'].'"' : ''; ?>
                    ><?php echo get_form_value($field_name); ?></textarea>
                <?php elseif ($field_config['type'] == 'select'): ?>
                    <select 
                        id="<?php echo $field_name; ?>"
                        name="<?php echo $field_name; ?>" 
                        class="form-control"
                    >
                        <option value="">Select...</option>
                        <?php if (isset($field_config['enum'])): ?>
                            <?php foreach ($field_config['enum'] as $option_value => $option_label): ?>
                                <option value="<?php echo $option_value; ?>" <?php echo (get_form_value($field_name) == $option_value) ? 'selected' : ''; ?>>
                                    <?php echo $option_label; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                <?php elseif ($field_config['type'] == 'checkbox'): ?>
                    <div class="form-check">
                        <input 
                            type="checkbox" 
                            id="<?php echo $field_name; ?>"
                            name="<?php echo $field_name; ?>" 
                            value="1"
                            class="form-check-input"
                            <?php echo (get_form_value($field_name) == '1') ? 'checked' : ''; ?>
                        />
                        <label class="form-check-label" for="<?php echo $field_name; ?>">
                            <?php echo isset($field_config['help_text']) ? $field_config['help_text'] : 'Yes'; ?>
                        </label>
                    </div>
                <?php elseif ($field_config['type'] == 'radio'): ?>
                    <?php if (isset($field_config['enum'])): ?>
                        <?php foreach ($field_config['enum'] as $option_value => $option_label): ?>
                            <div class="form-check">
                                <input 
                                    type="radio" 
                                    id="<?php echo $field_name . '_' . $option_value; ?>"
                                    name="<?php echo $field_name; ?>" 
                                    value="<?php echo $option_value; ?>"
                                    class="form-check-input"
                                    <?php echo (get_form_value($field_name) == $option_value) ? 'checked' : ''; ?>
                                />
                                <label class="form-check-label" for="<?php echo $field_name . '_' . $option_value; ?>">
                                    <?php echo $option_label; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php elseif ($field_config['type'] == 'date'): ?>
                    <input 
                        type="date" 
                        id="<?php echo $field_name; ?>"
                        name="<?php echo $field_name; ?>" 
                        class="form-control"
                        value="<?php echo get_form_value($field_name); ?>"
                        <?php echo (isset($field_config['placeholder'])) ? 'placeholder="'.$field_config['placeholder'].'"' : ''; ?>
                    />
                <?php elseif ($field_config['type'] == 'number'): ?>
                    <input 
                        type="number" 
                        id="<?php echo $field_name; ?>"
                        name="<?php echo $field_name; ?>" 
                        class="form-control"
                        value="<?php echo get_form_value($field_name); ?>"
                        <?php echo (isset($field_config['placeholder'])) ? 'placeholder="'.$field_config['placeholder'].'"' : ''; ?>
                    />
                <?php elseif ($field_config['type'] == 'email'): ?>
                    <input 
                        type="email" 
                        id="<?php echo $field_name; ?>"
                        name="<?php echo $field_name; ?>" 
                        class="form-control"
                        value="<?php echo get_form_value($field_name); ?>"
                        <?php echo (isset($field_config['placeholder'])) ? 'placeholder="'.$field_config['placeholder'].'"' : ''; ?>
                    />
                <?php elseif ($field_config['type'] == 'url'): ?>
                    <input 
                        type="url" 
                        id="<?php echo $field_name; ?>"
                        name="<?php echo $field_name; ?>" 
                        class="form-control"
                        value="<?php echo get_form_value($field_name); ?>"
                        <?php echo (isset($field_config['placeholder'])) ? 'placeholder="'.$field_config['placeholder'].'"' : ''; ?>
                    />
                <?php elseif ($field_config['type'] == 'phone'): ?>
                    <input 
                        type="tel" 
                        id="<?php echo $field_name; ?>"
                        name="<?php echo $field_name; ?>" 
                        class="form-control"
                        value="<?php echo get_form_value($field_name); ?>"
                        <?php echo (isset($field_config['placeholder'])) ? 'placeholder="'.$field_config['placeholder'].'"' : ''; ?>
                    />
                <?php else: ?>
                    <!-- Default text input -->
                    <input 
                        type="text" 
                        id="<?php echo $field_name; ?>"
                        name="<?php echo $field_name; ?>" 
                        class="form-control"
                        value="<?php echo get_form_value($field_name); ?>"
                        <?php echo (isset($field_config['placeholder'])) ? 'placeholder="'.$field_config['placeholder'].'"' : ''; ?>
                    />
                <?php endif; ?>
                
                <?php if (isset($field_config['help_text']) && !empty($field_config['help_text']) && $field_config['type'] != 'checkbox'): ?>
                    <small class="form-text text-muted"><?php echo $field_config['help_text']; ?></small>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?> 