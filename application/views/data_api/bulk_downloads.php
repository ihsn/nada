<div id="bulk-downloads" class="bulk-downloads-container mb-5" v-if="bulk_downloads.length > 0 || bulk_downloads_loading">
    
    <h3><?php echo t('Bulk data downloads');?></h3>
    
    <div v-if="bulk_downloads_loading" class="text-center py-4">
        <v-skeleton-loader type="table-row-divider@3"></v-skeleton-loader>
    </div>

    <div v-else>
        
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th><?php echo t('File');?></th>
                        <th><?php echo t('Size');?></th>
                        <th><?php echo t('Date');?></th>
                        <th><?php echo t('Actions');?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="file in bulk_downloads">
                        <td style="max-width: 400px;">
                            <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" :title="file.title">
                                <strong style="font-size:1.2em;">{{ file.title }}</strong>
                            </div>
                            <div v-if="file.filename" class="text-muted" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" :title="file.filename">
                                <i class="far fa-file"></i> {{ file.filename }}
                            </div>
                        </td>
                        <td class="text-nowrap small">
                            <span v-if="file.file_size">{{ file.file_size }}</span>
                            <span v-else class="text-muted">-</span>
                        </td>
                        <td class="text-nowrap small">
                            {{ formatDate(file.changed) }}
                        </td>
                        <td class="text-nowrap">
                            <a v-if="!file.external_link" 
                               :href="file.links.download" 
                               class="btn btn-sm btn-outline-primary" 
                               style="min-width: 100px;"
                               target="_blank"
                               :title="'<?php echo t('Download');?>: ' + file.filename">
                                <i class="fas fa-download"></i> 
                                {{ getFormatLabel(file.dcformat) }}                                
                            </a>
                            <a v-else
                               :href="file.filename" 
                               class="btn btn-sm btn-outline-primary" 
                               style="min-width: 100px;"
                               target="_blank"
                               :title="'<?php echo t('External link');?>: ' + file.filename">
                                <i class="fas fa-external-link-alt"></i> 
                                {{ getFormatLabel(file.dcformat) }}                                
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

     </div>

</div>

