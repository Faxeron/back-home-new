DataTable
DataTable displays data in tabular format.

Import

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import ColumnGroup from 'primevue/columngroup';   // optional
import Row from 'primevue/row';                   // optional

Basic
DataTable requires a value as data to display and Column components as children for the representation.

Code
Name
Category
Quantity
f230fh0g3	Bamboo Watch	Accessories	24
nvklal433	Black Watch	Accessories	61
zz21cz3c1	Blue Band	Fitness	2
244wgerg2	Blue T-Shirt	Clothing	25
h456wer53	Bracelet	Accessories	73

<DataTable :value="products" tableStyle="min-width: 50rem">
    <Column field="code" header="Code"></Column>
    <Column field="name" header="Name"></Column>
    <Column field="category" header="Category"></Column>
    <Column field="quantity" header="Quantity"></Column>
</DataTable>

Dynamic Columns
Columns can be created programmatically.

Code
Name
Category
Quantity
f230fh0g3	Bamboo Watch	Accessories	24
nvklal433	Black Watch	Accessories	61
zz21cz3c1	Blue Band	Fitness	2
244wgerg2	Blue T-Shirt	Clothing	25
h456wer53	Bracelet	Accessories	73

<DataTable :value="products" tableStyle="min-width: 50rem">
    <Column v-for="col of columns" :key="col.field" :field="col.field" :header="col.header"></Column>
</DataTable>

Template
Custom content at header and footer sections are supported via templating.

Products
Name
Image
Price
Category
Reviews
Status
Bamboo Watch	bamboo-watch.jpg	$65.00	Accessories	




	
INSTOCK
Black Watch	black-watch.jpg	$72.00	Accessories	




	
INSTOCK
Blue Band	blue-band.jpg	$79.00	Fitness	




	
LOWSTOCK
Blue T-Shirt	blue-t-shirt.jpg	$29.00	Clothing	




	
INSTOCK
Bracelet	bracelet.jpg	$15.00	Accessories	




	
INSTOCK
In total there are 5 products.

<DataTable :value="products" tableStyle="min-width: 50rem">
    <template #header>
        <div class="flex flex-wrap items-center justify-between gap-2">
            <span class="text-xl font-bold">Products</span>
            <Button icon="pi pi-refresh" rounded raised />
        </div>
    </template>
    <Column field="name" header="Name"></Column>
    <Column header="Image">
        <template #body="slotProps">
            <img :src="`https://primefaces.org/cdn/primevue/images/product/${slotProps.data.image}`" :alt="slotProps.data.image" class="w-24 rounded" />
        </template>
    </Column>
    <Column field="price" header="Price">
        <template #body="slotProps">
            {{ formatCurrency(slotProps.data.price) }}
        </template>
    </Column>
    <Column field="category" header="Category"></Column>
    <Column field="rating" header="Reviews">
        <template #body="slotProps">
            <Rating :modelValue="slotProps.data.rating" readonly />
        </template>
    </Column>
    <Column header="Status">
        <template #body="slotProps">
            <Tag :value="slotProps.data.inventoryStatus" :severity="getSeverity(slotProps.data)" />
        </template>
    </Column>
    <template #footer> In total there are {{ products ? products.length : 0 }} products. </template>
</DataTable>

Size
In addition to a regular table, alternatives with alternative sizes are available.

Code
Name
Category
Quantity
f230fh0g3	Bamboo Watch	Accessories	24
nvklal433	Black Watch	Accessories	61
zz21cz3c1	Blue Band	Fitness	2
244wgerg2	Blue T-Shirt	Clothing	25
h456wer53	Bracelet	Accessories	73

<SelectButton v-model="size" :options="sizeOptions" optionLabel="label" dataKey="label" />
<DataTable :value="products" :size="size.value" tableStyle="min-width: 50rem">
    <Column field="code" header="Code"></Column>
    <Column field="name" header="Name"></Column>
    <Column field="category" header="Category"></Column>
    <Column field="quantity" header="Quantity"></Column>
</DataTable>

Grid Lines
Enabling showGridlines displays borders between cells.

Code
Name
Category
Quantity
f230fh0g3	Bamboo Watch	Accessories	24
nvklal433	Black Watch	Accessories	61
zz21cz3c1	Blue Band	Fitness	2
244wgerg2	Blue T-Shirt	Clothing	25
h456wer53	Bracelet	Accessories	73

<DataTable :value="products" showGridlines tableStyle="min-width: 50rem">
    <Column field="code" header="Code"></Column>
    <Column field="name" header="Name"></Column>
    <Column field="category" header="Category"></Column>
    <Column field="quantity" header="Quantity"></Column>
</DataTable>

Striped Rows
Alternating rows are displayed when stripedRows property is present.

Code
Name
Category
Quantity
f230fh0g3	Bamboo Watch	Accessories	24
nvklal433	Black Watch	Accessories	61
zz21cz3c1	Blue Band	Fitness	2
244wgerg2	Blue T-Shirt	Clothing	25
h456wer53	Bracelet	Accessories	73

<DataTable :value="products" stripedRows tableStyle="min-width: 50rem">
    <Column field="code" header="Code"></Column>
    <Column field="name" header="Name"></Column>
    <Column field="category" header="Category"></Column>
    <Column field="quantity" header="Quantity"></Column>
</DataTable>

Pagination
Basic
Pagination is enabled by adding paginator property and defining rows per page.

Name
Country
Company
Representative
James Butt	Algeria	Benton, John B Jr	Ioni Bowcher
Josephine Darakjy	Egypt	Chanay, Jeffrey A Esq	Amy Elsner
Art Venere	Panama	Chemel, James L Cpa	Asiya Javayant
Lenna Paprocki	Slovenia	Feltz Printing Service	Xuxue Feng
Donette Foller	South Africa	Printing Dimensions	Asiya Javayant

<DataTable :value="customers" paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem">
    <Column field="name" header="Name" style="width: 25%"></Column>
    <Column field="country.name" header="Country" style="width: 25%"></Column>
    <Column field="company" header="Company" style="width: 25%"></Column>
    <Column field="representative.name" header="Representative" style="width: 25%"></Column>
</DataTable>

Template
Paginator UI is customized using the paginatorTemplate property. Each element can also be customized further with your own UI to replace the default one, refer to the Paginator component for more information about the advanced customization options.

Name
Country
Company
Representative
James Butt	Algeria	Benton, John B Jr	Ioni Bowcher
Josephine Darakjy	Egypt	Chanay, Jeffrey A Esq	Amy Elsner
Art Venere	Panama	Chemel, James L Cpa	Asiya Javayant
Lenna Paprocki	Slovenia	Feltz Printing Service	Xuxue Feng
Donette Foller	South Africa	Printing Dimensions	Asiya Javayant
1 to 5 of 50

<DataTable :value="customers" paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem"
        paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
        currentPageReportTemplate="{first} to {last} of {totalRecords}">
    <template #paginatorstart>
        <Button type="button" icon="pi pi-refresh" text />
    </template>
    <template #paginatorend>
        <Button type="button" icon="pi pi-download" text />
    </template>
    <Column field="name" header="Name" style="width: 25%"></Column>
    <Column field="country.name" header="Country" style="width: 25%"></Column>
    <Column field="company" header="Company" style="width: 25%"></Column>
    <Column field="representative.name" header="Representative" style="width: 25%"></Column>
</DataTable>

Headless
Headless mode on Pagination is enabled by adding using paginatorcontainer.

Name
Country
Company
Representative
James Butt	Algeria	Benton, John B Jr	Ioni Bowcher
Josephine Darakjy	Egypt	Chanay, Jeffrey A Esq	Amy Elsner
Art Venere	Panama	Chemel, James L Cpa	Asiya Javayant
Lenna Paprocki	Slovenia	Feltz Printing Service	Xuxue Feng
Donette Foller	South Africa	Printing Dimensions	Asiya Javayant
Showing 1 to 5 of 50

<DataTable :value="customers" paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem">
    <Column field="name" header="Name" style="width: 25%"></Column>
    <Column field="country.name" header="Country" style="width: 25%"></Column>
    <Column field="company" header="Company" style="width: 25%"></Column>
    <Column field="representative.name" header="Representative" style="width: 25%"></Column>
    <template #paginatorcontainer="{ first, last, page, pageCount, prevPageCallback, nextPageCallback, totalRecords }">
        <div class="flex items-center gap-4 border border-primary bg-transparent rounded-full w-full py-1 px-2 justify-between">
            <Button icon="pi pi-chevron-left" rounded text @click="prevPageCallback" :disabled="page === 0" />
            <div class="text-color font-medium">
                <span class="hidden sm:block">Showing {{ first }} to {{ last }} of {{ totalRecords }}</span>
                <span class="block sm:hidden">Page {{ page + 1 }} of {{ pageCount }}</span>
            </div>
            <Button icon="pi pi-chevron-right" rounded text @click="nextPageCallback" :disabled="page === pageCount - 1" />
        </div>
    </template>
</DataTable>

Sort
Single Column
Sorting on a column is enabled by adding the sortable property.

f230fh0g3	Bamboo Watch	Accessories	24
nvklal433	Black Watch	Accessories	61
zz21cz3c1	Blue Band	Fitness	2
244wgerg2	Blue T-Shirt	Clothing	25
h456wer53	Bracelet	Accessories	73

<DataTable :value="products" tableStyle="min-width: 50rem">
    <Column field="code" header="Code" sortable style="width: 25%"></Column>
    <Column field="name" header="Name" sortable style="width: 25%"></Column>
    <Column field="category" header="Category" sortable style="width: 25%"></Column>
    <Column field="quantity" header="Quantity" sortable style="width: 25%"></Column>
</DataTable>

Multiple Columns
Multiple columns can be sorted by defining sortMode as multiple. This mode requires metaKey (e.g. ⌘) to be pressed when clicking a header.

f230fh0g3	Bamboo Watch	Accessories	24
nvklal433	Black Watch	Accessories	61
zz21cz3c1	Blue Band	Fitness	2
244wgerg2	Blue T-Shirt	Clothing	25
h456wer53	Bracelet	Accessories	73

<DataTable :value="products" sortMode="multiple" tableStyle="min-width: 50rem">
    <Column field="code" header="Code" sortable style="width: 25%"></Column>
    <Column field="name" header="Name" sortable style="width: 25%"></Column>
    <Column field="category" header="Category" sortable style="width: 25%"></Column>
    <Column field="quantity" header="Quantity" sortable style="width: 25%"></Column>
</DataTable>

Presort
Defining a default sortField and sortOrder displays data as sorted initially in single column sorting. In multiple sort mode, multiSortMeta should be used instead by providing an array of DataTableSortMeta objects.

zz21cz3c1	Blue Band	$79.00	Fitness	2
nvklal433	Black Watch	$72.00	Accessories	61
f230fh0g3	Bamboo Watch	$65.00	Accessories	24
244wgerg2	Blue T-Shirt	$29.00	Clothing	25
h456wer53	Bracelet	$15.00	Accessories	73

<DataTable :value="products" sortField="price" :sortOrder="-1" tableStyle="min-width: 50rem">
    <Column field="code" header="Code" sortable style="width: 20%"></Column>
    <Column field="name" header="Name" sortable style="width: 20%"></Column>
    <Column field="price" header="Price" :sortable="true">
        <template #body="slotProps">
            {{ formatCurrency(slotProps.data.price) }}
        </template>
    </Column>
    <Column field="category" header="Category" sortable style="width: 20%"></Column>
    <Column field="quantity" header="Quantity" sortable style="width: 20%"></Column>
</DataTable>

Removable
When removableSort is present, the third click removes the sorting from the column.

f230fh0g3	Bamboo Watch	Accessories	24
nvklal433	Black Watch	Accessories	61
zz21cz3c1	Blue Band	Fitness	2
244wgerg2	Blue T-Shirt	Clothing	25
h456wer53	Bracelet	Accessories	73

<DataTable :value="products" removableSort tableStyle="min-width: 50rem">
    <Column field="code" header="Code" sortable style="width: 25%"></Column>
    <Column field="name" header="Name" sortable style="width: 25%"></Column>
    <Column field="category" header="Category" sortable style="width: 25%"></Column>
    <Column field="quantity" header="Quantity" sortable style="width: 25%"></Column>
</DataTable>

Filter
Basic
Data filtering is enabled by defining the filters model referring to a DataTableFilterMeta instance and specifying a filter element for a column using the filter template. This template receives a filterModel and filterCallback to build your own UI.

The optional global filtering searches the data against a single value that is bound to the global key of the filters object. The fields to search against are defined with the globalFilterFields.

Keyword Search
Name
Country
Agent
Status
Verified
jaa
Search by country
No customers found.

<DataTable v-model:filters="filters" :value="customers" paginator :rows="10" dataKey="id" filterDisplay="row" :loading="loading"
        :globalFilterFields="['name', 'country.name', 'representative.name', 'status']">
    <template #header>
        <div class="flex justify-end">
            <IconField>
                <InputIcon>
                    <i class="pi pi-search" />
                </InputIcon>
                <InputText v-model="filters['global'].value" placeholder="Keyword Search" />
            </IconField>
        </div>
    </template>
    <template #empty> No customers found. </template>
    <template #loading> Loading customers data. Please wait. </template>
    <Column field="name" header="Name" style="min-width: 12rem">
        <template #body="{ data }">
            {{ data.name }}
        </template>
        <template #filter="{ filterModel, filterCallback }">
            <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Search by name" />
        </template>
    </Column>
    <Column header="Country" filterField="country.name" style="min-width: 12rem">
        <template #body="{ data }">
            <div class="flex items-center gap-2">
                <img alt="flag" src="https://primefaces.org/cdn/primevue/images/flag/flag_placeholder.png" :class="`flag flag-${data.country.code}`" style="width: 24px" />
                <span>{{ data.country.name }}</span>
            </div>
        </template>
        <template #filter="{ filterModel, filterCallback }">
            <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Search by country" />
        </template>
    </Column>
    <Column header="Agent" filterField="representative" :showFilterMenu="false" style="min-width: 14rem">
        <template #body="{ data }">
            <div class="flex items-center gap-2">
                <img :alt="data.representative.name" :src="`https://primefaces.org/cdn/primevue/images/avatar/${data.representative.image}`" style="width: 32px" />
                <span>{{ data.representative.name }}</span>
            </div>
        </template>
        <template #filter="{ filterModel, filterCallback }">
            <MultiSelect v-model="filterModel.value" @change="filterCallback()" :options="representatives" optionLabel="name" placeholder="Any" style="min-width: 14rem" :maxSelectedLabels="1">
                <template #option="slotProps">
                    <div class="flex items-center gap-2">
                        <img :alt="slotProps.option.name" :src="`https://primefaces.org/cdn/primevue/images/avatar/${slotProps.option.image}`" style="width: 32px" />
                        <span>{{ slotProps.option.name }}</span>
                    </div>
                </template>
            </MultiSelect>
        </template>
    </Column>
    <Column field="status" header="Status" :showFilterMenu="false" style="min-width: 12rem">
        <template #body="{ data }">
            <Tag :value="data.status" :severity="getSeverity(data.status)" />
        </template>
        <template #filter="{ filterModel, filterCallback }">
            <Select v-model="filterModel.value" @change="filterCallback()" :options="statuses" placeholder="Select One" style="min-width: 12rem" :showClear="true">
                <template #option="slotProps">
                    <Tag :value="slotProps.option" :severity="getSeverity(slotProps.option)" />
                </template>
            </Select>
        </template>
    </Column>
    <Column field="verified" header="Verified" dataType="boolean" style="min-width: 6rem">
        <template #body="{ data }">
            <i class="pi" :class="{ 'pi-check-circle text-green-500': data.verified, 'pi-times-circle text-red-400': !data.verified }"></i>
        </template>
        <template #filter="{ filterModel, filterCallback }">
            <Checkbox v-model="filterModel.value" :indeterminate="filterModel.value === null" binary @change="filterCallback()" />
        </template>
    </Column>
</DataTable>

Advanced
When filterDisplay is set as menu, filtering UI is placed inside a popover with support for multiple constraints and advanced templating.

Keyword Search
Name
Country
Agent
Date
Balance
Status
Activity
Verified
James Butt	
flag
Algeria
Ioni Bowcher
Ioni Bowcher
13.09.2015	$70,663.00	
unqualified
Josephine Darakjy	
flag
Egypt
Amy Elsner
Amy Elsner
09.02.2019	$82,429.00	
negotiation
Art Venere	
flag
Panama
Asiya Javayant
Asiya Javayant
13.05.2017	$28,334.00	
qualified
Lenna Paprocki	
flag
Slovenia
Xuxue Feng
Xuxue Feng
15.09.2020	$88,521.00	
new
Donette Foller	
flag
South Africa
Asiya Javayant
Asiya Javayant
20.05.2016	$93,905.00	
negotiation
Simona Morasca	
flag
Egypt
Ivan Magalhaes
Ivan Magalhaes
16.02.2018	$50,041.00	
qualified
Mitsue Tollner	
flag
Paraguay
Ivan Magalhaes
Ivan Magalhaes
19.02.2018	$58,706.00	
renewal
Leota Dilliard	
flag
Serbia
Onyama Limba
Onyama Limba
13.08.2019	$26,640.00	
renewal
Sage Wieser	
flag
Egypt
Ivan Magalhaes
Ivan Magalhaes
21.11.2018	$65,369.00	
unqualified
Kris Marrier	
flag
Mexico
Onyama Limba
Onyama Limba
07.07.2015	$63,451.00	
negotiation

<DataTable v-model:filters="filters" :value="customers" paginator showGridlines :rows="10" dataKey="id"
        filterDisplay="menu" :loading="loading" :globalFilterFields="['name', 'country.name', 'representative.name', 'balance', 'status']">
    <template #header>
        <div class="flex justify-between">
            <Button type="button" icon="pi pi-filter-slash" label="Clear" variant="outlined" @click="clearFilter()" />
            <IconField>
                <InputIcon>
                    <i class="pi pi-search" />
                </InputIcon>
                <InputText v-model="filters['global'].value" placeholder="Keyword Search" />
            </IconField>
        </div>
    </template>
    <template #empty> No customers found. </template>
    <template #loading> Loading customers data. Please wait. </template>
    <Column field="name" header="Name" style="min-width: 12rem">
        <template #body="{ data }">
            {{ data.name }}
        </template>
        <template #filter="{ filterModel }">
            <InputText v-model="filterModel.value" type="text" placeholder="Search by name" />
        </template>
    </Column>
    <Column header="Country" filterField="country.name" style="min-width: 12rem">
        <template #body="{ data }">
            <div class="flex items-center gap-2">
                <img alt="flag" src="https://primefaces.org/cdn/primevue/images/flag/flag_placeholder.png" :class="`flag flag-${data.country.code}`" style="width: 24px" />
                <span>{{ data.country.name }}</span>
            </div>
        </template>
        <template #filter="{ filterModel }">
            <InputText v-model="filterModel.value" type="text" placeholder="Search by country" />
        </template>
        <template #filterclear="{ filterCallback }">
            <Button type="button" icon="pi pi-times" @click="filterCallback()" severity="secondary"></Button>
        </template>
        <template #filterapply="{ filterCallback }">
            <Button type="button" icon="pi pi-check" @click="filterCallback()" severity="success"></Button>
        </template>
        <template #filterfooter>
            <div class="px-4 pt-0 pb-4 text-center">Customized Buttons</div>
        </template>
    </Column>
    <Column header="Agent" filterField="representative" :showFilterMatchModes="false" :filterMenuStyle="{ width: '14rem' }" style="min-width: 14rem">
        <template #body="{ data }">
            <div class="flex items-center gap-2">
                <img :alt="data.representative.name" :src="`https://primefaces.org/cdn/primevue/images/avatar/${data.representative.image}`" style="width: 32px" />
                <span>{{ data.representative.name }}</span>
            </div>
        </template>
        <template #filter="{ filterModel }">
            <MultiSelect v-model="filterModel.value" :options="representatives" optionLabel="name" placeholder="Any">
                <template #option="slotProps">
                    <div class="flex items-center gap-2">
                        <img :alt="slotProps.option.name" :src="`https://primefaces.org/cdn/primevue/images/avatar/${slotProps.option.image}`" style="width: 32px" />
                        <span>{{ slotProps.option.name }}</span>
                    </div>
                </template>
            </MultiSelect>
        </template>
    </Column>
    <Column header="Date" filterField="date" dataType="date" style="min-width: 10rem">
        <template #body="{ data }">
            {{ formatDate(data.date) }}
        </template>
        <template #filter="{ filterModel }">
            <DatePicker v-model="filterModel.value" dateFormat="mm/dd/yy" placeholder="mm/dd/yyyy" />
        </template>
    </Column>
    <Column header="Balance" filterField="balance" dataType="numeric" style="min-width: 10rem">
        <template #body="{ data }">
            {{ formatCurrency(data.balance) }}
        </template>
        <template #filter="{ filterModel }">
            <InputNumber v-model="filterModel.value" mode="currency" currency="USD" locale="en-US" />
        </template>
    </Column>
    <Column header="Status" field="status" :filterMenuStyle="{ width: '14rem' }" style="min-width: 12rem">
        <template #body="{ data }">
            <Tag :value="data.status" :severity="getSeverity(data.status)" />
        </template>
        <template #filter="{ filterModel }">
            <Select v-model="filterModel.value" :options="statuses" placeholder="Select One" showClear>
                <template #option="slotProps">
                    <Tag :value="slotProps.option" :severity="getSeverity(slotProps.option)" />
                </template>
            </Select>
        </template>
    </Column>
    <Column field="activity" header="Activity" :showFilterMatchModes="false" style="min-width: 12rem">
        <template #body="{ data }">
            <ProgressBar :value="data.activity" :showValue="false" style="height: 6px"></ProgressBar>
        </template>
        <template #filter="{ filterModel }">
            <Slider v-model="filterModel.value" range class="m-4"></Slider>
            <div class="flex items-center justify-between px-2">
                <span>{{ filterModel.value ? filterModel.value[0] : 0 }}</span>
                <span>{{ filterModel.value ? filterModel.value[1] : 100 }}</span>
            </div>
        </template>
    </Column>
    <Column field="verified" header="Verified" dataType="boolean" bodyClass="text-center" style="min-width: 8rem">
        <template #body="{ data }">
            <i class="pi" :class="{ 'pi-check-circle text-green-500 ': data.verified, 'pi-times-circle text-red-500': !data.verified }"></i>
        </template>
        <template #filter="{ filterModel }">
            <label for="verified-filter" class="font-bold"> Verified </label>
            <Checkbox v-model="filterModel.value" :indeterminate="filterModel.value === null" binary inputId="verified-filter" />
        </template>
    </Column>
</DataTable>

Row Selection
Single
Single row selection is enabled by defining selectionMode as single along with a value binding using selection property. When available, it is suggested to provide a unique identifier of a row with dataKey to optimize performance.

By default, metaKey press (e.g. ⌘) is necessary to unselect a row however this can be configured with disabling the metaKeySelection property. In touch enabled devices this option has no effect and behavior is same as setting it to false.


MetaKey
Code
Name
Category
Quantity
f230fh0g3	Bamboo Watch	Accessories	24
nvklal433	Black Watch	Accessories	61
zz21cz3c1	Blue Band	Fitness	2
244wgerg2	Blue T-Shirt	Clothing	25
h456wer53	Bracelet	Accessories	73

<ToggleSwitch v-model="metaKey" inputId="input-metakey" />

<DataTable v-model:selection="selectedProduct" :value="products" selectionMode="single" :metaKeySelection="metaKey" dataKey="id" tableStyle="min-width: 50rem">
    <Column field="code" header="Code"></Column>
    <Column field="name" header="Name"></Column>
    <Column field="category" header="Category"></Column>
    <Column field="quantity" header="Quantity"></Column>
</DataTable>

Multiple
More than one row is selectable by setting selectionMode to multiple. By default in multiple selection mode, metaKey press (e.g. ⌘) is not necessary to add to existing selections. When the optional metaKeySelection is present, behavior is changed in a way that selecting a new row requires meta key to be present. Note that in touch enabled devices, DataTable always ignores metaKey.


MetaKey
Code
Name
Category
Quantity
f230fh0g3	Bamboo Watch	Accessories	24
nvklal433	Black Watch	Accessories	61
zz21cz3c1	Blue Band	Fitness	2
244wgerg2	Blue T-Shirt	Clothing	25
h456wer53	Bracelet	Accessories	73

<ToggleSwitch v-model="metaKey" inputId="input-metakey" />

<DataTable v-model:selection="selectedProducts" :value="products" selectionMode="multiple" :metaKeySelection="metaKey" dataKey="id" tableStyle="min-width: 50rem">
    <Column field="code" header="Code"></Column>
    <Column field="name" header="Name"></Column>
    <Column field="category" header="Category"></Column>
    <Column field="quantity" header="Quantity"></Column>
</DataTable>

RadioButton
Specifying selectionMode as single on a Column, displays a radio button inside that column for selection. By default, row clicks also trigger selection, set selectionMode of DataTable to radiobutton to only trigger selection using the radio buttons.

Code
Name
Category
Quantity
f230fh0g3	Bamboo Watch	Accessories	24
nvklal433	Black Watch	Accessories	61
zz21cz3c1	Blue Band	Fitness	2
244wgerg2	Blue T-Shirt	Clothing	25
h456wer53	Bracelet	Accessories	73

<DataTable v-model:selection="selectedProduct" :value="products" dataKey="id" tableStyle="min-width: 50rem">
    <Column selectionMode="single" headerStyle="width: 3rem"></Column>
    <Column field="code" header="Code"></Column>
    <Column field="name" header="Name"></Column>
    <Column field="category" header="Category"></Column>
    <Column field="quantity" header="Quantity"></Column>
</DataTable>

Checkbox
Specifying selectionMode as multiple on a Column, displays a checkbox inside that column for selection.

The header checkbox toggles the selection state of the whole dataset by default, when paginator is enabled you may add selectAll property and select-all-change event to only control the selection of visible rows.

Code
Name
Category
Quantity
f230fh0g3	Bamboo Watch	Accessories	24
nvklal433	Black Watch	Accessories	61
zz21cz3c1	Blue Band	Fitness	2
244wgerg2	Blue T-Shirt	Clothing	25
h456wer53	Bracelet	Accessories	73

<DataTable v-model:selection="selectedProducts" :value="products" dataKey="id" tableStyle="min-width: 50rem">
    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
    <Column field="code" header="Code"></Column>
    <Column field="name" header="Name"></Column>
    <Column field="category" header="Category"></Column>
    <Column field="quantity" header="Quantity"></Column>
</DataTable>

Column
Row selection with an element inside a column is implemented with templating.

Code
Name
Category
Quantity
f230fh0g3	Bamboo Watch	Accessories	24	
nvklal433	Black Watch	Accessories	61	
zz21cz3c1	Blue Band	Fitness	2	
244wgerg2	Blue T-Shirt	Clothing	25	
h456wer53	Bracelet	Accessories	73	

<DataTable :value="products" tableStyle="min-width: 50rem">
    <Column field="code" header="Code"></Column>
    <Column field="name" header="Name"></Column>
    <Column field="category" header="Category"></Column>
    <Column field="quantity" header="Quantity"></Column>
    <Column class="w-24 !text-end">
        <template #body="{ data }">
            <Button icon="pi pi-search" @click="selectRow(data)" severity="secondary" rounded></Button>
        </template>
    </Column>
</DataTable>

Events
DataTable provides row-select and row-unselect events to listen selection events.

Code
Name
Category
Quantity
f230fh0g3	Bamboo Watch	Accessories	24
nvklal433	Black Watch	Accessories	61
zz21cz3c1	Blue Band	Fitness	2
244wgerg2	Blue T-Shirt	Clothing	25
h456wer53	Bracelet	Accessories	73

<DataTable v-model:selection="selectedProduct" :value="products" selectionMode="single" dataKey="id" :metaKeySelection="false"
        @rowSelect="onRowSelect" @rowUnselect="onRowUnselect" tableStyle="min-width: 50rem">
    <Column selectionMode="single" headerStyle="width: 3rem"></Column>
    <Column field="code" header="Code"></Column>
    <Column field="name" header="Name"></Column>
    <Column field="category" header="Category"></Column>
    <Column field="quantity" header="Quantity"></Column>
</DataTable>

Row Expansion
Row expansion is controlled with expandedRows property. The column that has the expander element requires expander property to be enabled. Optional rowExpand and rowCollapse events are available as callbacks.

Expanded rows can either be an array of row data or when dataKey is present, an object whose keys are strings referring to the identifier of the row data and values are booleans to represent the expansion state e.g. {'1004': true}. The dataKey alternative is more performant for large amounts of data.

Name
Image
Price
Category
Reviews
Status
Bamboo Watch	bamboo-watch.jpg	$65.00	Accessories	




	
INSTOCK
Black Watch	black-watch.jpg	$72.00	Accessories	




	
INSTOCK
Blue Band	blue-band.jpg	$79.00	Fitness	




	
LOWSTOCK
Blue T-Shirt	blue-t-shirt.jpg	$29.00	Clothing	




	
INSTOCK
Bracelet	bracelet.jpg	$15.00	Accessories	




	
INSTOCK
Brown Purse	brown-purse.jpg	$120.00	Accessories	




	
OUTOFSTOCK
Chakra Bracelet	chakra-bracelet.jpg	$32.00	Accessories	




	
LOWSTOCK
Galaxy Earrings	galaxy-earrings.jpg	$34.00	Accessories	




	
INSTOCK
Game Controller	game-controller.jpg	$99.00	Electronics	




	
LOWSTOCK
Gaming Set	gaming-set.jpg	$299.00	Electronics	




	
INSTOCK

<DataTable v-model:expandedRows="expandedRows" :value="products" dataKey="id"
        @rowExpand="onRowExpand" @rowCollapse="onRowCollapse" tableStyle="min-width: 60rem">
    <template #header>
        <div class="flex flex-wrap justify-end gap-2">
            <Button variant="text" icon="pi pi-plus" label="Expand All" @click="expandAll" />
            <Button variant="text" icon="pi pi-minus" label="Collapse All" @click="collapseAll" />
        </div>
    </template>
    <Column expander style="width: 5rem" />
    <Column field="name" header="Name"></Column>
    <Column header="Image">
        <template #body="slotProps">
            <img :src="`https://primefaces.org/cdn/primevue/images/product/${slotProps.data.image}`" :alt="slotProps.data.image" class="shadow-lg" width="64" />
        </template>
    </Column>
    <Column field="price" header="Price">
        <template #body="slotProps">
            {{ formatCurrency(slotProps.data.price) }}
        </template>
    </Column>
    <Column field="category" header="Category"></Column>
    <Column field="rating" header="Reviews">
        <template #body="slotProps">
            <Rating :modelValue="slotProps.data.rating" readonly />
        </template>
    </Column>
    <Column header="Status">
        <template #body="slotProps">
            <Tag :value="slotProps.data.inventoryStatus" :severity="getSeverity(slotProps.data)" />
        </template>
    </Column>
    <template #expansion="slotProps">
        <div class="p-4">
            <h5>Orders for {{ slotProps.data.name }}</h5>
            <DataTable :value="slotProps.data.orders">
                <Column field="id" header="Id" sortable></Column>
                <Column field="customer" header="Customer" sortable></Column>
                <Column field="date" header="Date" sortable></Column>
                <Column field="amount" header="Amount" sortable>
                    <template #body="slotProps">
                        {{ formatCurrency(slotProps.data.amount) }}
                    </template>
                </Column>
                <Column field="status" header="Status" sortable>
                    <template #body="slotProps">
                        <Tag :value="slotProps.data.status.toLowerCase()" :severity="getOrderSeverity(slotProps.data)" />
                    </template>
                </Column>
                <Column headerStyle="width:4rem">
                    <template #body>
                        <Button icon="pi pi-search" />
                    </template>
                </Column>
            </DataTable>
        </div>
    </template>
</DataTable>

Edit
Cell
Cell editing is enabled by setting editMode as cell, defining input elements with editor templating of a Column and implementing cell-edit-complete to update the state.

Code
Name
Quantity
Price
f230fh0g3	Bamboo Watch	24	$65.00
nvklal433	Black Watch	61	$72.00
zz21cz3c1	Blue Band	2	$79.00
244wgerg2	Blue T-Shirt	25	$29.00
h456wer53	Bracelet	73	$15.00

<DataTable :value="products" editMode="cell" @cell-edit-complete="onCellEditComplete"
    :pt="{
        table: { style: 'min-width: 50rem' },
        column: {
            bodycell: ({ state }) => ({
                class: [{ '!py-0': state['d_editing'] }]
            })
        }
    }"
>
    <Column v-for="col of columns" :key="col.field" :field="col.field" :header="col.header" style="width: 25%">
        <template #body="{ data, field }">
            {{ field === 'price' ? formatCurrency(data[field]) : data[field] }}
        </template>
        <template #editor="{ data, field }">
            <template v-if="field !== 'price'">
                <InputText v-model="data[field]" autofocus fluid />
            </template>
            <template v-else>
                <InputNumber v-model="data[field]" mode="currency" currency="USD" locale="en-US" autofocus fluid />
            </template>
        </template>
    </Column>
</DataTable>

Row
Row editing is configured with setting editMode as row and defining editingRows with the v-model directive to hold the reference of the editing rows. Similarly with cell edit mode, defining input elements with editor slot of a Column and implementing row-edit-save are necessary to update the state. The column to control the editing state should have editor templating applied.

Code
Name
Status
Price
f230fh0g3	Bamboo Watch	
INSTOCK
$65.00	
nvklal433	Black Watch	
INSTOCK
$72.00	
zz21cz3c1	Blue Band	
LOWSTOCK
$79.00	
244wgerg2	Blue T-Shirt	
INSTOCK
$29.00	
h456wer53	Bracelet	
INSTOCK
$15.00	

<DataTable v-model:editingRows="editingRows" :value="products" editMode="row" dataKey="id" @row-edit-save="onRowEditSave"
    :pt="{
        table: { style: 'min-width: 50rem' },
        column: {
            bodycell: ({ state }) => ({
                style:  state['d_editing']&&'padding-top: 0.75rem; padding-bottom: 0.75rem'
            })
        }
    }"
>
    <Column field="code" header="Code" style="width: 20%">
        <template #editor="{ data, field }">
            <InputText v-model="data[field]" />
        </template>
    </Column>
    <Column field="name" header="Name" style="width: 20%">
        <template #editor="{ data, field }">
            <InputText v-model="data[field]" fluid />
        </template>
    </Column>
    <Column field="inventoryStatus" header="Status" style="width: 20%">
        <template #editor="{ data, field }">
            <Select v-model="data[field]" :options="statuses" optionLabel="label" optionValue="value" placeholder="Select a Status" fluid>
                <template #option="slotProps">
                    <Tag :value="slotProps.option.value" :severity="getStatusLabel(slotProps.option.value)" />
                </template>
            </Select>
        </template>
        <template #body="slotProps">
            <Tag :value="slotProps.data.inventoryStatus" :severity="getStatusLabel(slotProps.data.inventoryStatus)" />
        </template>
    </Column>
    <Column field="price" header="Price" style="width: 20%">
        <template #body="{ data, field }">
            {{ formatCurrency(data[field]) }}
        </template>
        <template #editor="{ data, field }">
            <InputNumber v-model="data[field]" mode="currency" currency="USD" locale="en-US" fluid />
        </template>
    </Column>
    <Column :rowEditor="true" style="width: 10%; min-width: 8rem" bodyStyle="text-align:center"></Column>
</DataTable>

Scroll
Vertical
Adding scrollable property along with a scrollHeight for the data viewport enables vertical scrolling with fixed headers.

Name
Country
Representative
Company
James Butt	Algeria	Ioni Bowcher	Benton, John B Jr
Josephine Darakjy	Egypt	Amy Elsner	Chanay, Jeffrey A Esq
Art Venere	Panama	Asiya Javayant	Chemel, James L Cpa
Lenna Paprocki	Slovenia	Xuxue Feng	Feltz Printing Service
Donette Foller	South Africa	Asiya Javayant	Printing Dimensions
Simona Morasca	Egypt	Ivan Magalhaes	Chapman, Ross E Esq
Mitsue Tollner	Paraguay	Ivan Magalhaes	Morlong Associates
Leota Dilliard	Serbia	Onyama Limba	Commercial Press
Sage Wieser	Egypt	Ivan Magalhaes	Truhlar And Truhlar Attys
Kris Marrier	Mexico	Onyama Limba	King, Christopher A Esq
Minna Amigon	Romania	Anna Fali	Dorl, James J Esq
Abel Maclead	Singapore	Bernardo Dominic	Rangoni Of Florence
Kiley Caldarera	Serbia	Onyama Limba	Feiner Bros
Graciela Ruta	Chile	Amy Elsner	Buckley Miller & Wright
Cammy Albares	Philippines	Asiya Javayant	Rousseaux, Michael Esq
Mattie Poquette	Venezuela	Anna Fali	Century Communications
Meaghan Garufi	Malaysia	Ivan Magalhaes	Bolton, Wilbur Esq
Gladys Rim	Netherlands	Stephen Shaw	T M Byxbee Company Pc
Yuki Whobrey	Israel	Bernardo Dominic	Farmers Insurance Group
Fletcher Flosi	Argentina	Xuxue Feng	Post Box Services Plus
Bette Nicka	Paraguay	Onyama Limba	Sport En Art
Veronika Inouye	Ecuador	Ioni Bowcher	C 4 Network Inc
Willard Kolmetz	Tunisia	Asiya Javayant	Ingalls, Donald R Esq
Maryann Royster	Belarus	Elwin Sharvill	Franklin, Peter L Esq
Alisha Slusarski	Iceland	Stephen Shaw	Wtlz Power 107 Fm
Allene Iturbide	Italy	Ivan Magalhaes	Ledecky, David Esq
Chanel Caudy	Argentina	Ioni Bowcher	Professional Image Inc
Ezekiel Chui	Ireland	Amy Elsner	Sider, Donald C Esq
Willow Kusko	Romania	Onyama Limba	U Pull It
Bernardo Figeroa	Israel	Ioni Bowcher	Clark, Richard Cpa
Ammie Corrio	Hungary	Asiya Javayant	Moskowitz, Barry S
Francine Vocelka	Honduras	Ioni Bowcher	Cascade Realty Advisors Inc
Ernie Stenseth	Australia	Xuxue Feng	Knwz Newsradio
Albina Glick	Ukraine	Bernardo Dominic	Giampetro, Anthony D
Alishia Sergi	Qatar	Ivan Magalhaes	Milford Enterprises Inc
Solange Shinko	Cameroon	Onyama Limba	Mosocco, Ronald A
Jose Stockham	Italy	Amy Elsner	Tri State Refueler Co
Rozella Ostrosky	Venezuela	Amy Elsner	Parkway Company
Valentine Gillian	Paraguay	Bernardo Dominic	Fbs Business Finance
Kati Rulapaugh	Puerto Rico	Ioni Bowcher	Eder Assocs Consltng Engrs Pc
Youlanda Schemmer	Bolivia	Xuxue Feng	Tri M Tool Inc
Dyan Oldroyd	Argentina	Amy Elsner	International Eyelets Inc
Roxane Campain	France	Anna Fali	Rapid Trading Intl
Lavera Perin	Vietnam	Stephen Shaw	Abc Enterprises Inc
Erick Ferencz	Belgium	Amy Elsner	Cindy Turner Associates
Fatima Saylors	Canada	Onyama Limba	Stanton, James D Esq
Jina Briddick	Mexico	Xuxue Feng	Grace Pastries Inc
Kanisha Waycott	Ecuador	Xuxue Feng	Schroer, Gene E Esq
Emerson Bowley	Finland	Stephen Shaw	Knights Inn
Blair Malet	Finland	Asiya Javayant	Bollinger Mach Shp & Shipyard

<DataTable :value="customers" scrollable scrollHeight="400px" tableStyle="min-width: 50rem">
    <Column field="name" header="Name"></Column>
    <Column field="country.name" header="Country"></Column>
    <Column field="representative.name" header="Representative"></Column>
    <Column field="company" header="Company"></Column>
</DataTable>

Flexible
Flex scroll feature makes the scrollable viewport section dynamic instead of a fixed value so that it can grow or shrink relative to the parent size of the table. Click the button below to display a maximizable Dialog where data viewport adjusts itself according to the size changes.


<Button label="Show" icon="pi pi-external-link" @click="dialogVisible = true" />
<Dialog v-model:visible="dialogVisible" header="Flex Scroll" :style="{ width: '75vw' }" maximizable modal :contentStyle="{ height: '300px' }">
    <DataTable :value="customers" scrollable scrollHeight="flex" tableStyle="min-width: 50rem">
        <Column field="name" header="Name"></Column>
        <Column field="country.name" header="Country"></Column>
        <Column field="representative.name" header="Representative"></Column>
        <Column field="company" header="Company"></Column>
    </DataTable>
    <template #footer>
        <Button label="Ok" icon="pi pi-check" @click="dialogVisible = false" />
    </template>
</Dialog>

Horizontal
Horizontal scrollbar is displayed when table width exceeds the parent width.

Id
Name
Country
Date
Balance
Company
Status
Activity
Representative
1000	James Butt	Algeria	2015-09-13	$70,663.00	Benton, John B Jr	unqualified	17	Ioni Bowcher
1001	Josephine Darakjy	Egypt	2019-02-09	$82,429.00	Chanay, Jeffrey A Esq	negotiation	0	Amy Elsner
1002	Art Venere	Panama	2017-05-13	$28,334.00	Chemel, James L Cpa	qualified	63	Asiya Javayant
1003	Lenna Paprocki	Slovenia	2020-09-15	$88,521.00	Feltz Printing Service	new	37	Xuxue Feng
1004	Donette Foller	South Africa	2016-05-20	$93,905.00	Printing Dimensions	negotiation	33	Asiya Javayant
1005	Simona Morasca	Egypt	2018-02-16	$50,041.00	Chapman, Ross E Esq	qualified	68	Ivan Magalhaes
1006	Mitsue Tollner	Paraguay	2018-02-19	$58,706.00	Morlong Associates	renewal	54	Ivan Magalhaes
1007	Leota Dilliard	Serbia	2019-08-13	$26,640.00	Commercial Press	renewal	69	Onyama Limba
1008	Sage Wieser	Egypt	2018-11-21	$65,369.00	Truhlar And Truhlar Attys	unqualified	76	Ivan Magalhaes
1009	Kris Marrier	Mexico	2015-07-07	$63,451.00	King, Christopher A Esq	negotiation	3	Onyama Limba
1010	Minna Amigon	Romania	2018-11-07	$71,169.00	Dorl, James J Esq	qualified	38	Anna Fali
1011	Abel Maclead	Singapore	2017-03-11	$96,842.00	Rangoni Of Florence	qualified	87	Bernardo Dominic
1012	Kiley Caldarera	Serbia	2015-10-20	$92,734.00	Feiner Bros	unqualified	80	Onyama Limba
1013	Graciela Ruta	Chile	2016-07-25	$45,250.00	Buckley Miller & Wright	negotiation	59	Amy Elsner
1014	Cammy Albares	Philippines	2019-06-25	$30,236.00	Rousseaux, Michael Esq	new	90	Asiya Javayant
1015	Mattie Poquette	Venezuela	2017-12-12	$64,533.00	Century Communications	negotiation	52	Anna Fali
1016	Meaghan Garufi	Malaysia	2018-07-04	$37,279.00	Bolton, Wilbur Esq	unqualified	31	Ivan Magalhaes
1017	Gladys Rim	Netherlands	2020-02-27	$27,381.00	T M Byxbee Company Pc	renewal	48	Stephen Shaw
1018	Yuki Whobrey	Israel	2017-12-21	$9,257.00	Farmers Insurance Group	negotiation	16	Bernardo Dominic
1019	Fletcher Flosi	Argentina	2016-01-04	$67,783.00	Post Box Services Plus	renewal	19	Xuxue Feng
1020	Bette Nicka	Paraguay	2016-10-21	$4,609.00	Sport En Art	renewal	100	Onyama Limba
1021	Veronika Inouye	Ecuador	2017-03-24	$26,565.00	C 4 Network Inc	renewal	72	Ioni Bowcher
1022	Willard Kolmetz	Tunisia	2017-04-15	$75,876.00	Ingalls, Donald R Esq	renewal	94	Asiya Javayant
1023	Maryann Royster	Belarus	2017-03-11	$41,121.00	Franklin, Peter L Esq	qualified	56	Elwin Sharvill
1024	Alisha Slusarski	Iceland	2018-03-27	$91,691.00	Wtlz Power 107 Fm	qualified	7	Stephen Shaw
1025	Allene Iturbide	Italy	2016-02-20	$40,137.00	Ledecky, David Esq	qualified	1	Ivan Magalhaes
1026	Chanel Caudy	Argentina	2018-06-24	$21,304.00	Professional Image Inc	new	26	Ioni Bowcher
1027	Ezekiel Chui	Ireland	2016-09-24	$60,454.00	Sider, Donald C Esq	new	76	Amy Elsner
1028	Willow Kusko	Romania	2020-04-11	$17,565.00	U Pull It	qualified	7	Onyama Limba
1029	Bernardo Figeroa	Israel	2018-04-11	$17,774.00	Clark, Richard Cpa	renewal	81	Ioni Bowcher
1030	Ammie Corrio	Hungary	2016-06-11	$49,201.00	Moskowitz, Barry S	negotiation	56	Asiya Javayant
1031	Francine Vocelka	Honduras	2017-08-02	$67,126.00	Cascade Realty Advisors Inc	qualified	94	Ioni Bowcher
1032	Ernie Stenseth	Australia	2018-06-06	$76,017.00	Knwz Newsradio	renewal	68	Xuxue Feng
1033	Albina Glick	Ukraine	2019-08-08	$91,201.00	Giampetro, Anthony D	negotiation	85	Bernardo Dominic
1034	Alishia Sergi	Qatar	2018-05-19	$12,237.00	Milford Enterprises Inc	negotiation	46	Ivan Magalhaes
1035	Solange Shinko	Cameroon	2015-02-12	$34,072.00	Mosocco, Ronald A	qualified	32	Onyama Limba
1036	Jose Stockham	Italy	2018-04-25	$94,909.00	Tri State Refueler Co	qualified	77	Amy Elsner
1037	Rozella Ostrosky	Venezuela	2016-02-27	$57,245.00	Parkway Company	unqualified	66	Amy Elsner
1038	Valentine Gillian	Paraguay	2019-09-17	$75,502.00	Fbs Business Finance	qualified	25	Bernardo Dominic
1039	Kati Rulapaugh	Puerto Rico	2016-12-03	$82,075.00	Eder Assocs Consltng Engrs Pc	renewal	51	Ioni Bowcher
1040	Youlanda Schemmer	Bolivia	2017-12-15	$19,208.00	Tri M Tool Inc	negotiation	49	Xuxue Feng
1041	Dyan Oldroyd	Argentina	2017-02-02	$50,194.00	International Eyelets Inc	qualified	5	Amy Elsner
1042	Roxane Campain	France	2018-12-25	$77,714.00	Rapid Trading Intl	unqualified	100	Anna Fali
1043	Lavera Perin	Vietnam	2018-04-10	$35,740.00	Abc Enterprises Inc	qualified	71	Stephen Shaw
1044	Erick Ferencz	Belgium	2018-05-06	$30,790.00	Cindy Turner Associates	unqualified	54	Amy Elsner
1045	Fatima Saylors	Canada	2019-07-10	$52,343.00	Stanton, James D Esq	renewal	93	Onyama Limba
1046	Jina Briddick	Mexico	2018-02-19	$53,966.00	Grace Pastries Inc	unqualified	97	Xuxue Feng
1047	Kanisha Waycott	Ecuador	2019-11-27	$9,920.00	Schroer, Gene E Esq	new	80	Xuxue Feng
1048	Emerson Bowley	Finland	2018-11-24	$78,069.00	Knights Inn	new	63	Stephen Shaw
1049	Blair Malet	Finland	2018-04-19	$65,005.00	Bollinger Mach Shp & Shipyard	new	92	Asiya Javayant
Id	Name	Country	Date	Balance	Company	Status	Activity	Representative

<DataTable :value="customers" scrollable scrollHeight="400px">
    <Column field="id" header="Id" footer="Id" style="min-width: 100px"></Column>
    <Column field="name" header="Name" footer="Name" style="min-width: 200px"></Column>
    <Column field="country.name" header="Country" footer="Country" style="min-width: 200px"></Column>
    <Column field="date" header="Date" footer="Date" style="min-width: 200px"></Column>
    <Column field="balance" header="Balance" footer="Balance" style="min-width: 200px">
        <template #body="{ data }">
            {{ formatCurrency(data.balance) }}
        </template>
    </Column>
    <Column field="company" header="Company" footer="Company" style="min-width: 200px"></Column>
    <Column field="status" header="Status" footer="Status" style="min-width: 200px"></Column>
    <Column field="activity" header="Activity" footer="Activity" style="min-width: 200px"></Column>
    <Column field="representative.name" header="Representative" footer="Representative" style="min-width: 200px"></Column>
</DataTable>

Frozen Rows
Rows can be fixed during scrolling by enabling the frozenValue property.

Name
Country
Representative
Status
Geraldine Bisset	France	Amy Elsner	proposal	
James Butt	Algeria	Ioni Bowcher	unqualified	
Josephine Darakjy	Egypt	Amy Elsner	negotiation	
Art Venere	Panama	Asiya Javayant	qualified	
Lenna Paprocki	Slovenia	Xuxue Feng	new	
Donette Foller	South Africa	Asiya Javayant	negotiation	
Simona Morasca	Egypt	Ivan Magalhaes	qualified	
Mitsue Tollner	Paraguay	Ivan Magalhaes	renewal	
Leota Dilliard	Serbia	Onyama Limba	renewal	
Sage Wieser	Egypt	Ivan Magalhaes	unqualified	
Kris Marrier	Mexico	Onyama Limba	negotiation	
Minna Amigon	Romania	Anna Fali	qualified	
Abel Maclead	Singapore	Bernardo Dominic	qualified	
Kiley Caldarera	Serbia	Onyama Limba	unqualified	
Graciela Ruta	Chile	Amy Elsner	negotiation	
Cammy Albares	Philippines	Asiya Javayant	new	
Mattie Poquette	Venezuela	Anna Fali	negotiation	
Meaghan Garufi	Malaysia	Ivan Magalhaes	unqualified	
Gladys Rim	Netherlands	Stephen Shaw	renewal	
Yuki Whobrey	Israel	Bernardo Dominic	negotiation	
Fletcher Flosi	Argentina	Xuxue Feng	renewal	
Bette Nicka	Paraguay	Onyama Limba	renewal	
Veronika Inouye	Ecuador	Ioni Bowcher	renewal	
Willard Kolmetz	Tunisia	Asiya Javayant	renewal	
Maryann Royster	Belarus	Elwin Sharvill	qualified	
Alisha Slusarski	Iceland	Stephen Shaw	qualified	
Allene Iturbide	Italy	Ivan Magalhaes	qualified	
Chanel Caudy	Argentina	Ioni Bowcher	new	
Ezekiel Chui	Ireland	Amy Elsner	new	
Willow Kusko	Romania	Onyama Limba	qualified	
Bernardo Figeroa	Israel	Ioni Bowcher	renewal	
Ammie Corrio	Hungary	Asiya Javayant	negotiation	
Francine Vocelka	Honduras	Ioni Bowcher	qualified	
Ernie Stenseth	Australia	Xuxue Feng	renewal	
Albina Glick	Ukraine	Bernardo Dominic	negotiation	
Alishia Sergi	Qatar	Ivan Magalhaes	negotiation	
Solange Shinko	Cameroon	Onyama Limba	qualified	
Jose Stockham	Italy	Amy Elsner	qualified	
Rozella Ostrosky	Venezuela	Amy Elsner	unqualified	
Valentine Gillian	Paraguay	Bernardo Dominic	qualified	
Kati Rulapaugh	Puerto Rico	Ioni Bowcher	renewal	
Youlanda Schemmer	Bolivia	Xuxue Feng	negotiation	
Dyan Oldroyd	Argentina	Amy Elsner	qualified	
Roxane Campain	France	Anna Fali	unqualified	
Lavera Perin	Vietnam	Stephen Shaw	qualified	
Erick Ferencz	Belgium	Amy Elsner	unqualified	
Fatima Saylors	Canada	Onyama Limba	renewal	
Jina Briddick	Mexico	Xuxue Feng	unqualified	
Kanisha Waycott	Ecuador	Xuxue Feng	new	
Emerson Bowley	Finland	Stephen Shaw	new	
Blair Malet	Finland	Asiya Javayant	new	

<DataTable
    :value="customers"
    :frozenValue="lockedCustomers"
    scrollable
    scrollHeight="400px"
    :pt="{
        table: { style: 'min-width: 50rem' },
        bodyrow: ({ props }) => ({
            class: [{ 'font-bold': props.frozenRow }]
        })
    }"
>
    <Column field="name" header="Name"></Column>
    <Column field="country.name" header="Country"></Column>
    <Column field="representative.name" header="Representative"></Column>
    <Column field="status" header="Status"></Column>
    <Column style="flex: 0 0 4rem">
        <template #body="{ data, frozenRow, index }">
            <Button type="button" :icon="frozenRow ? 'pi pi-lock-open' : 'pi pi-lock'" :disabled="frozenRow ? false : lockedCustomers.length >= 2" text size="small" @click="toggleLock(data, frozenRow, index)" />
        </template>
    </Column>
</DataTable>

Frozen Columns
A column can be fixed during horizontal scrolling by enabling the frozen property. The location is defined with the alignFrozen that can be left or right.

Name
Id
Name
Country
Date
Company
Status
Activity
Representative
Balance
James Butt	1000	James Butt	Algeria	2015-09-13	Benton, John B Jr	unqualified	17	Ioni Bowcher	$70,663.00
Josephine Darakjy	1001	Josephine Darakjy	Egypt	2019-02-09	Chanay, Jeffrey A Esq	negotiation	0	Amy Elsner	$82,429.00
Art Venere	1002	Art Venere	Panama	2017-05-13	Chemel, James L Cpa	qualified	63	Asiya Javayant	$28,334.00
Lenna Paprocki	1003	Lenna Paprocki	Slovenia	2020-09-15	Feltz Printing Service	new	37	Xuxue Feng	$88,521.00
Donette Foller	1004	Donette Foller	South Africa	2016-05-20	Printing Dimensions	negotiation	33	Asiya Javayant	$93,905.00
Simona Morasca	1005	Simona Morasca	Egypt	2018-02-16	Chapman, Ross E Esq	qualified	68	Ivan Magalhaes	$50,041.00
Mitsue Tollner	1006	Mitsue Tollner	Paraguay	2018-02-19	Morlong Associates	renewal	54	Ivan Magalhaes	$58,706.00
Leota Dilliard	1007	Leota Dilliard	Serbia	2019-08-13	Commercial Press	renewal	69	Onyama Limba	$26,640.00
Sage Wieser	1008	Sage Wieser	Egypt	2018-11-21	Truhlar And Truhlar Attys	unqualified	76	Ivan Magalhaes	$65,369.00
Kris Marrier	1009	Kris Marrier	Mexico	2015-07-07	King, Christopher A Esq	negotiation	3	Onyama Limba	$63,451.00
Minna Amigon	1010	Minna Amigon	Romania	2018-11-07	Dorl, James J Esq	qualified	38	Anna Fali	$71,169.00
Abel Maclead	1011	Abel Maclead	Singapore	2017-03-11	Rangoni Of Florence	qualified	87	Bernardo Dominic	$96,842.00
Kiley Caldarera	1012	Kiley Caldarera	Serbia	2015-10-20	Feiner Bros	unqualified	80	Onyama Limba	$92,734.00
Graciela Ruta	1013	Graciela Ruta	Chile	2016-07-25	Buckley Miller & Wright	negotiation	59	Amy Elsner	$45,250.00
Cammy Albares	1014	Cammy Albares	Philippines	2019-06-25	Rousseaux, Michael Esq	new	90	Asiya Javayant	$30,236.00
Mattie Poquette	1015	Mattie Poquette	Venezuela	2017-12-12	Century Communications	negotiation	52	Anna Fali	$64,533.00
Meaghan Garufi	1016	Meaghan Garufi	Malaysia	2018-07-04	Bolton, Wilbur Esq	unqualified	31	Ivan Magalhaes	$37,279.00
Gladys Rim	1017	Gladys Rim	Netherlands	2020-02-27	T M Byxbee Company Pc	renewal	48	Stephen Shaw	$27,381.00
Yuki Whobrey	1018	Yuki Whobrey	Israel	2017-12-21	Farmers Insurance Group	negotiation	16	Bernardo Dominic	$9,257.00
Fletcher Flosi	1019	Fletcher Flosi	Argentina	2016-01-04	Post Box Services Plus	renewal	19	Xuxue Feng	$67,783.00
Bette Nicka	1020	Bette Nicka	Paraguay	2016-10-21	Sport En Art	renewal	100	Onyama Limba	$4,609.00
Veronika Inouye	1021	Veronika Inouye	Ecuador	2017-03-24	C 4 Network Inc	renewal	72	Ioni Bowcher	$26,565.00
Willard Kolmetz	1022	Willard Kolmetz	Tunisia	2017-04-15	Ingalls, Donald R Esq	renewal	94	Asiya Javayant	$75,876.00
Maryann Royster	1023	Maryann Royster	Belarus	2017-03-11	Franklin, Peter L Esq	qualified	56	Elwin Sharvill	$41,121.00
Alisha Slusarski	1024	Alisha Slusarski	Iceland	2018-03-27	Wtlz Power 107 Fm	qualified	7	Stephen Shaw	$91,691.00
Allene Iturbide	1025	Allene Iturbide	Italy	2016-02-20	Ledecky, David Esq	qualified	1	Ivan Magalhaes	$40,137.00
Chanel Caudy	1026	Chanel Caudy	Argentina	2018-06-24	Professional Image Inc	new	26	Ioni Bowcher	$21,304.00
Ezekiel Chui	1027	Ezekiel Chui	Ireland	2016-09-24	Sider, Donald C Esq	new	76	Amy Elsner	$60,454.00
Willow Kusko	1028	Willow Kusko	Romania	2020-04-11	U Pull It	qualified	7	Onyama Limba	$17,565.00
Bernardo Figeroa	1029	Bernardo Figeroa	Israel	2018-04-11	Clark, Richard Cpa	renewal	81	Ioni Bowcher	$17,774.00
Ammie Corrio	1030	Ammie Corrio	Hungary	2016-06-11	Moskowitz, Barry S	negotiation	56	Asiya Javayant	$49,201.00
Francine Vocelka	1031	Francine Vocelka	Honduras	2017-08-02	Cascade Realty Advisors Inc	qualified	94	Ioni Bowcher	$67,126.00
Ernie Stenseth	1032	Ernie Stenseth	Australia	2018-06-06	Knwz Newsradio	renewal	68	Xuxue Feng	$76,017.00
Albina Glick	1033	Albina Glick	Ukraine	2019-08-08	Giampetro, Anthony D	negotiation	85	Bernardo Dominic	$91,201.00
Alishia Sergi	1034	Alishia Sergi	Qatar	2018-05-19	Milford Enterprises Inc	negotiation	46	Ivan Magalhaes	$12,237.00
Solange Shinko	1035	Solange Shinko	Cameroon	2015-02-12	Mosocco, Ronald A	qualified	32	Onyama Limba	$34,072.00
Jose Stockham	1036	Jose Stockham	Italy	2018-04-25	Tri State Refueler Co	qualified	77	Amy Elsner	$94,909.00
Rozella Ostrosky	1037	Rozella Ostrosky	Venezuela	2016-02-27	Parkway Company	unqualified	66	Amy Elsner	$57,245.00
Valentine Gillian	1038	Valentine Gillian	Paraguay	2019-09-17	Fbs Business Finance	qualified	25	Bernardo Dominic	$75,502.00
Kati Rulapaugh	1039	Kati Rulapaugh	Puerto Rico	2016-12-03	Eder Assocs Consltng Engrs Pc	renewal	51	Ioni Bowcher	$82,075.00
Youlanda Schemmer	1040	Youlanda Schemmer	Bolivia	2017-12-15	Tri M Tool Inc	negotiation	49	Xuxue Feng	$19,208.00
Dyan Oldroyd	1041	Dyan Oldroyd	Argentina	2017-02-02	International Eyelets Inc	qualified	5	Amy Elsner	$50,194.00
Roxane Campain	1042	Roxane Campain	France	2018-12-25	Rapid Trading Intl	unqualified	100	Anna Fali	$77,714.00
Lavera Perin	1043	Lavera Perin	Vietnam	2018-04-10	Abc Enterprises Inc	qualified	71	Stephen Shaw	$35,740.00
Erick Ferencz	1044	Erick Ferencz	Belgium	2018-05-06	Cindy Turner Associates	unqualified	54	Amy Elsner	$30,790.00
Fatima Saylors	1045	Fatima Saylors	Canada	2019-07-10	Stanton, James D Esq	renewal	93	Onyama Limba	$52,343.00
Jina Briddick	1046	Jina Briddick	Mexico	2018-02-19	Grace Pastries Inc	unqualified	97	Xuxue Feng	$53,966.00
Kanisha Waycott	1047	Kanisha Waycott	Ecuador	2019-11-27	Schroer, Gene E Esq	new	80	Xuxue Feng	$9,920.00
Emerson Bowley	1048	Emerson Bowley	Finland	2018-11-24	Knights Inn	new	63	Stephen Shaw	$78,069.00
Blair Malet	1049	Blair Malet	Finland	2018-04-19	Bollinger Mach Shp & Shipyard	new	92	Asiya Javayant	$65,005.00
Brock Bolognia	1050	Brock Bolognia	Bolivia	2019-09-06	Orinda News	renewal	72	Onyama Limba	$51,038.00
Lorrie Nestle	1051	Lorrie Nestle	Germany	2018-04-26	Ballard Spahr Andrews	renewal	36	Anna Fali	$28,218.00
Sabra Uyetake	1052	Sabra Uyetake	Peru	2018-04-12	Lowy Limousine Service	new	31	Amy Elsner	$78,527.00
Marjory Mastella	1053	Marjory Mastella	Netherlands	2018-01-24	Vicon Corporation	negotiation	89	Anna Fali	$23,381.00
Karl Klonowski	1054	Karl Klonowski	Saudi Arabia	2017-04-17	Rossi, Michael M	unqualified	27	Onyama Limba	$64,821.00
Tonette Wenner	1055	Tonette Wenner	Australia	2019-04-14	Northwest Publishing	qualified	27	Elwin Sharvill	$55,334.00
Amber Monarrez	1056	Amber Monarrez	Sweden	2019-09-09	Branford Wire & Mfg Co	new	79	Bernardo Dominic	$83,391.00
Shenika Seewald	1057	Shenika Seewald	Australia	2017-02-18	East Coast Marketing	renewal	39	Xuxue Feng	$31,580.00
Delmy Ahle	1058	Delmy Ahle	Belgium	2020-10-05	Wye Technologies Inc	unqualified	55	Anna Fali	$11,723.00
Deeanna Juhas	1059	Deeanna Juhas	Sweden	2018-09-28	Healy, George W Iv	negotiation	79	Asiya Javayant	$8,454.00
Blondell Pugh	1060	Blondell Pugh	Ireland	2016-06-16	Alpenlite Inc	renewal	49	Bernardo Dominic	$99,235.00
Jamal Vanausdal	1061	Jamal Vanausdal	Morocco	2017-05-25	Hubbard, Bruce Esq	negotiation	87	Ioni Bowcher	$15,656.00
Cecily Hollack	1062	Cecily Hollack	Bolivia	2020-05-09	Arthur A Oliver & Son Inc	negotiation	5	Amy Elsner	$60,586.00
Carmelina Lindall	1063	Carmelina Lindall	Puerto Rico	2019-09-07	George Jessop Carter Jewelers	qualified	77	Asiya Javayant	$86,239.00
Maurine Yglesias	1064	Maurine Yglesias	Taiwan	2015-08-10	Schultz, Thomas C Md	renewal	94	Ioni Bowcher	$15,621.00
Tawna Buvens	1065	Tawna Buvens	Indonesia	2018-03-20	H H H Enterprises Inc	new	25	Amy Elsner	$77,248.00
Penney Weight	1066	Penney Weight	South Africa	2020-03-03	Hawaiian King Hotel	qualified	96	Amy Elsner	$478.00
Elly Morocco	1067	Elly Morocco	Thailand	2018-09-18	Killion Industries	qualified	38	Xuxue Feng	$62,505.00
Ilene Eroman	1068	Ilene Eroman	Netherlands	2019-06-08	Robinson, William J Esq	new	49	Anna Fali	$91,480.00
Vallie Mondella	1069	Vallie Mondella	Latvia	2018-12-06	Private Properties	new	16	Ivan Magalhaes	$21,671.00
Kallie Blackwood	1070	Kallie Blackwood	Iceland	2017-04-05	Rowley Schlimgen Inc	unqualified	25	Amy Elsner	$13,775.00
Johnetta Abdallah	1071	Johnetta Abdallah	Netherlands	2015-02-02	Forging Specialties	new	16	Elwin Sharvill	$60,253.00
Bobbye Rhym	1072	Bobbye Rhym	Ukraine	2018-08-17	Smits, Patricia Garity	qualified	85	Xuxue Feng	$75,225.00
Micaela Rhymes	1073	Micaela Rhymes	France	2018-09-08	H Lee Leonard Attorney At Law	renewal	92	Asiya Javayant	$3,308.00
Tamar Hoogland	1074	Tamar Hoogland	Guatemala	2018-11-13	A K Construction Co	negotiation	22	Asiya Javayant	$19,711.00
Moon Parlato	1075	Moon Parlato	Czech Republic	2019-08-18	Ambelang, Jessica M Md	renewal	64	Onyama Limba	$55,110.00
Laurel Reitler	1076	Laurel Reitler	United Kingdom	2015-04-02	Q A Service	negotiation	80	Amy Elsner	$62,392.00
Delisa Crupi	1077	Delisa Crupi	Taiwan	2017-09-15	Wood & Whitacre Contractors	unqualified	70	Xuxue Feng	$76,530.00
Viva Toelkes	1078	Viva Toelkes	United States	2017-03-27	Mark Iv Press Ltd	qualified	16	Stephen Shaw	$7,460.00
Elza Lipke	1079	Elza Lipke	Ireland	2017-06-01	Museum Of Science & Industry	negotiation	90	Elwin Sharvill	$42,251.00
Devorah Chickering	1080	Devorah Chickering	Spain	2017-03-14	Garrison Ind	negotiation	96	Asiya Javayant	$36,435.00
Timothy Mulqueen	1081	Timothy Mulqueen	Netherlands	2018-07-09	Saronix Nymph Products	renewal	77	Asiya Javayant	$39,197.00
Arlette Honeywell	1082	Arlette Honeywell	Panama	2018-09-11	Smc Inc	negotiation	46	Amy Elsner	$72,707.00
Dominque Dickerson	1083	Dominque Dickerson	Argentina	2017-11-12	E A I Electronic Assocs Inc	qualified	83	Bernardo Dominic	$97,965.00
Lettie Isenhower	1084	Lettie Isenhower	Canada	2016-03-01	Conte, Christopher A Esq	qualified	83	Bernardo Dominic	$5,823.00
Myra Munns	1085	Myra Munns	Lithuania	2016-05-21	Anker Law Office	unqualified	49	Elwin Sharvill	$96,471.00
Stephaine Barfield	1086	Stephaine Barfield	Belgium	2016-01-22	Beutelschies & Company	new	34	Anna Fali	$33,710.00
Lai Gato	1087	Lai Gato	Nigeria	2016-07-26	Fligg, Kenneth I Jr	unqualified	64	Onyama Limba	$30,611.00
Stephen Emigh	1088	Stephen Emigh	Cuba	2020-07-24	Sharp, J Daniel Esq	renewal	51	Elwin Sharvill	$32,960.00
Tyra Shields	1089	Tyra Shields	Honduras	2019-11-10	Assink, Anne H Esq	negotiation	11	Anna Fali	$57,423.00
Tammara Wardrip	1090	Tammara Wardrip	Saudi Arabia	2016-06-05	Jewel My Shop Inc	renewal	64	Xuxue Feng	$23,027.00
Cory Gibes	1091	Cory Gibes	Malaysia	2016-02-28	Chinese Translation Resources	new	44	Anna Fali	$84,182.00
Danica Bruschke	1092	Danica Bruschke	Taiwan	2018-12-13	Stevens, Charles T	unqualified	62	Stephen Shaw	$25,237.00
Wilda Giguere	1093	Wilda Giguere	Iceland	2017-06-16	Mclaughlin, Luther W Cpa	new	79	Asiya Javayant	$87,736.00
Elvera Benimadho	1094	Elvera Benimadho	Malaysia	2019-02-17	Tree Musketeers	negotiation	50	Onyama Limba	$38,674.00
Carma Vanheusen	1095	Carma Vanheusen	Turkey	2019-11-26	Springfield Div Oh Edison Co	renewal	84	Stephen Shaw	$67,762.00
Malinda Hochard	1096	Malinda Hochard	Serbia	2016-07-06	Logan Memorial Hospital	new	88	Asiya Javayant	$81,299.00
Natalie Fern	1097	Natalie Fern	Canada	2019-10-02	Kelly, Charles G Esq	negotiation	44	Amy Elsner	$64,794.00
Lisha Centini	1098	Lisha Centini	Netherlands	2018-07-05	Industrial Paper Shredders Inc	new	7	Ioni Bowcher	$7,815.00
Arlene Klusman	1099	Arlene Klusman	Jamaica	2018-05-14	Beck Horizon Builders	negotiation	99	Elwin Sharvill	$37,976.00
Alease Buemi	1100	Alease Buemi	Costa Rica	2018-03-14	Porto Cayo At Hawks Cay	unqualified	0	Onyama Limba	$59,594.00
Louisa Cronauer	1101	Louisa Cronauer	Costa Rica	2018-09-23	Pacific Grove Museum Ntrl Hist	qualified	3	Anna Fali	$92,528.00
Angella Cetta	1102	Angella Cetta	Vietnam	2018-04-10	Bender & Hatley Pc	qualified	88	Ivan Magalhaes	$58,964.00
Cyndy Goldammer	1103	Cyndy Goldammer	Burkina Faso	2017-09-18	Di Cristina J & Son	unqualified	92	Stephen Shaw	$65,860.00
Rosio Cork	1104	Rosio Cork	Singapore	2017-08-19	Green Goddess	negotiation	19	Asiya Javayant	$63,863.00
Celeste Korando	1105	Celeste Korando	Costa Rica	2020-06-18	American Arts & Graphics	negotiation	21	Amy Elsner	$37,510.00
Twana Felger	1106	Twana Felger	Croatia	2016-11-18	Opryland Hotel	negotiation	97	Ioni Bowcher	$63,876.00
Estrella Samu	1107	Estrella Samu	Vietnam	2017-06-25	Marking Devices Pubg Co	unqualified	27	Bernardo Dominic	$93,263.00
Donte Kines	1108	Donte Kines	Slovakia	2019-02-16	W Tc Industries Inc	new	35	Onyama Limba	$57,198.00
Tiffiny Steffensmeier	1109	Tiffiny Steffensmeier	Pakistan	2018-03-11	Whitehall Robbins Labs Divsn	new	81	Ivan Magalhaes	$89,147.00
Edna Miceli	1110	Edna Miceli	France	2017-10-15	Sampler	renewal	54	Asiya Javayant	$41,466.00
Sue Kownacki	1111	Sue Kownacki	Jamaica	2017-03-17	Juno Chefs Incorporated	negotiation	31	Onyama Limba	$38,918.00
Jesusa Shin	1112	Jesusa Shin	Ukraine	2017-04-06	Carroccio, A Thomas Esq	renewal	28	Bernardo Dominic	$11,397.00
Rolland Francescon	1113	Rolland Francescon	United Kingdom	2019-02-03	Stanley, Richard L Esq	qualified	45	Onyama Limba	$40,930.00
Pamella Schmierer	1114	Pamella Schmierer	Belgium	2016-09-22	K Cs Cstm Mouldings Windows	unqualified	34	Ioni Bowcher	$40,847.00
Glory Kulzer	1115	Glory Kulzer	Croatia	2017-09-27	Comfort Inn	unqualified	36	Onyama Limba	$27,832.00
Shawna Palaspas	1116	Shawna Palaspas	Estonia	2017-06-25	Windsor, James L Esq	unqualified	69	Bernardo Dominic	$89,060.00
Brandon Callaro	1117	Brandon Callaro	Romania	2016-07-13	Jackson Shields Yeiser	negotiation	55	Anna Fali	$52,474.00
Scarlet Cartan	1118	Scarlet Cartan	Panama	2018-09-13	Box, J Calvin Esq	renewal	1	Xuxue Feng	$19,094.00
Oretha Menter	1119	Oretha Menter	Panama	2017-09-11	Custom Engineering Inc	renewal	8	Elwin Sharvill	$93,756.00
Ty Smith	1120	Ty Smith	United States	2019-07-06	Bresler Eitel Framg Gllry Ltd	unqualified	50	Anna Fali	$77,388.00
Xuan Rochin	1121	Xuan Rochin	Colombia	2018-05-22	Carol, Drake Sparks Esq	negotiation	77	Amy Elsner	$48,759.00
Lindsey Dilello	1122	Lindsey Dilello	Austria	2017-07-18	Biltmore Investors Bank	renewal	65	Amy Elsner	$37,568.00
Devora Perez	1123	Devora Perez	Uruguay	2017-10-09	Desco Equipment Corp	unqualified	30	Onyama Limba	$4,477.00
Herman Demesa	1124	Herman Demesa	Paraguay	2019-05-23	Merlin Electric Co	negotiation	10	Asiya Javayant	$13,764.00
Rory Papasergi	1125	Rory Papasergi	Egypt	2019-03-02	Bailey Cntl Co Div Babcock	qualified	22	Anna Fali	$68,222.00
Talia Riopelle	1126	Talia Riopelle	Guatemala	2017-02-18	Ford Brothers Wholesale Inc	new	69	Elwin Sharvill	$29,164.00
Van Shire	1127	Van Shire	Netherlands	2020-05-12	Cambridge Inn	new	4	Ioni Bowcher	$61,651.00
Lucina Lary	1128	Lucina Lary	Switzerland	2019-11-20	Matricciani, Albert J Jr	negotiation	11	Xuxue Feng	$79,938.00
Bok Isaacs	1129	Bok Isaacs	Chile	2016-11-10	Nelson Hawaiian Ltd	negotiation	41	Asiya Javayant	$44,037.00
Rolande Spickerman	1130	Rolande Spickerman	Panama	2016-07-11	Neland Travel Agency	renewal	84	Bernardo Dominic	$89,918.00
Howard Paulas	1131	Howard Paulas	Indonesia	2017-07-17	Asendorf, J Alan Esq	negotiation	22	Ioni Bowcher	$32,372.00
Kimbery Madarang	1132	Kimbery Madarang	Senegal	2018-08-19	Silberman, Arthur L Esq	negotiation	63	Onyama Limba	$46,478.00
Thurman Manno	1133	Thurman Manno	Colombia	2016-05-02	Honey Bee Breeding Genetics &	qualified	47	Ivan Magalhaes	$30,674.00
Becky Mirafuentes	1134	Becky Mirafuentes	Serbia	2018-04-13	Wells Kravitz Schnitzer	unqualified	62	Elwin Sharvill	$47,714.00
Beatriz Corrington	1135	Beatriz Corrington	South Africa	2020-01-04	Prohab Rehabilitation Servs	renewal	55	Stephen Shaw	$14,307.00
Marti Maybury	1136	Marti Maybury	Thailand	2016-02-05	Eldridge, Kristin K Esq	unqualified	3	Bernardo Dominic	$82,069.00
Nieves Gotter	1137	Nieves Gotter	Latvia	2017-03-12	Vlahos, John J Esq	negotiation	3	Elwin Sharvill	$11,182.00
Leatha Hagele	1138	Leatha Hagele	Ukraine	2019-03-27	Ninas Indian Grs & Videos	unqualified	67	Stephen Shaw	$17,126.00
Valentin Klimek	1139	Valentin Klimek	Ivory Coast	2019-08-06	Schmid, Gayanne K Esq	unqualified	14	Ivan Magalhaes	$19,724.00
Melissa Wiklund	1140	Melissa Wiklund	Japan	2018-03-20	Moapa Valley Federal Credit Un	qualified	8	Onyama Limba	$91,888.00
Sheridan Zane	1141	Sheridan Zane	Croatia	2016-02-15	Kentucky Tennessee Clay Co	qualified	17	Bernardo Dominic	$15,016.00
Bulah Padilla	1142	Bulah Padilla	Philippines	2016-02-10	Admiral Party Rentals & Sales	negotiation	58	Ioni Bowcher	$23,118.00
Audra Kohnert	1143	Audra Kohnert	Netherlands	2019-07-16	Nelson, Karolyn King Esq	unqualified	82	Bernardo Dominic	$90,560.00
Daren Weirather	1144	Daren Weirather	Israel	2015-07-23	Panasystems	negotiation	96	Onyama Limba	$34,155.00
Fernanda Jillson	1145	Fernanda Jillson	Mexico	2017-07-02	Shank, Edward L Esq	unqualified	92	Xuxue Feng	$6,350.00
Gearldine Gellinger	1146	Gearldine Gellinger	Egypt	2019-08-17	Megibow & Edwards	negotiation	18	Anna Fali	$77,641.00
Chau Kitzman	1147	Chau Kitzman	Paraguay	2019-07-04	Benoff, Edward Esq	new	9	Onyama Limba	$43,289.00
Theola Frey	1148	Theola Frey	Vietnam	2020-03-14	Woodbridge Free Public Library	unqualified	44	Ioni Bowcher	$85,657.00
Cheryl Haroldson	1149	Cheryl Haroldson	France	2018-04-03	New York Life John Thune	new	55	Elwin Sharvill	$82,733.00
Laticia Merced	1150	Laticia Merced	Burkina Faso	2017-03-04	Alinabal Inc	unqualified	21	Ivan Magalhaes	$38,004.00
Carissa Batman	1151	Carissa Batman	Greece	2016-05-05	Poletto, Kim David Esq	negotiation	91	Ivan Magalhaes	$29,038.00
Lezlie Craghead	1152	Lezlie Craghead	Panama	2019-05-28	Chang, Carolyn Esq	renewal	30	Xuxue Feng	$75,123.00
Ozell Shealy	1153	Ozell Shealy	Pakistan	2016-08-19	Silver Bros Inc	negotiation	14	Bernardo Dominic	$33,214.00
Arminda Parvis	1154	Arminda Parvis	Indonesia	2020-02-09	Newtec Inc	negotiation	77	Elwin Sharvill	$80,651.00
Reita Leto	1155	Reita Leto	Belgium	2020-04-03	Creative Business Systems	unqualified	58	Ioni Bowcher	$5,085.00
Yolando Luczki	1156	Yolando Luczki	France	2015-01-27	Dal Tile Corporation	renewal	78	Ioni Bowcher	$93,021.00
Lizette Stem	1157	Lizette Stem	Slovakia	2018-08-06	Edward S Katz	new	67	Stephen Shaw	$37,287.00
Gregoria Pawlowicz	1158	Gregoria Pawlowicz	Egypt	2020-02-20	Oh My Goodknits Inc	renewal	29	Stephen Shaw	$73,070.00
Carin Deleo	1159	Carin Deleo	China	2015-05-28	Redeker, Debbie	qualified	13	Asiya Javayant	$64,422.00
Chantell Maynerich	1160	Chantell Maynerich	Estonia	2016-09-05	Desert Sands Motel	unqualified	75	Ivan Magalhaes	$36,826.00
Dierdre Yum	1161	Dierdre Yum	Czech Republic	2016-12-20	Cummins Southern Plains Inc	negotiation	1	Onyama Limba	$93,101.00
Larae Gudroe	1162	Larae Gudroe	Slovenia	2015-11-28	Lehigh Furn Divsn Lehigh	unqualified	13	Ioni Bowcher	$60,177.00
Latrice Tolfree	1163	Latrice Tolfree	Jamaica	2018-11-11	United Van Lines Agent	renewal	73	Ioni Bowcher	$47,198.00
Kerry Theodorov	1164	Kerry Theodorov	Romania	2016-11-05	Capitol Reporters	unqualified	76	Amy Elsner	$71,305.00
Dorthy Hidvegi	1165	Dorthy Hidvegi	Poland	2020-08-13	Kwik Kopy Printing	qualified	60	Ivan Magalhaes	$17,526.00
Fannie Lungren	1166	Fannie Lungren	Belarus	2015-07-06	Centro Inc	negotiation	24	Stephen Shaw	$16,596.00
Evangelina Radde	1167	Evangelina Radde	Ivory Coast	2020-02-25	Campbell, Jan Esq	unqualified	93	Anna Fali	$56,870.00
Novella Degroot	1168	Novella Degroot	Slovenia	2017-12-19	Evans, C Kelly Esq	unqualified	30	Amy Elsner	$82,928.00
Clay Hoa	1169	Clay Hoa	Paraguay	2016-02-22	Scat Enterprises	negotiation	93	Amy Elsner	$64,181.00
Jennifer Fallick	1170	Jennifer Fallick	Australia	2016-12-24	Nagle, Daniel J Esq	unqualified	88	Bernardo Dominic	$30,561.00
Irma Wolfgramm	1171	Irma Wolfgramm	Belgium	2020-10-18	Serendiquity Bed & Breakfast	negotiation	70	Stephen Shaw	$24,617.00
Eun Coody	1172	Eun Coody	Taiwan	2018-02-12	Ray Carolyne Realty	qualified	61	Ioni Bowcher	$77,860.00
Sylvia Cousey	1173	Sylvia Cousey	Ireland	2018-06-10	Berg, Charles E	unqualified	91	Ioni Bowcher	$25,664.00
Nana Wrinkles	1174	Nana Wrinkles	Austria	2017-04-11	Ray, Milbern D	renewal	98	Asiya Javayant	$98,113.00
Layla Springe	1175	Layla Springe	South Africa	2019-07-27	Chadds Ford Winery	unqualified	97	Ioni Bowcher	$14,763.00
Joesph Degonia	1176	Joesph Degonia	Serbia	2020-04-23	A R Packaging	renewal	56	Elwin Sharvill	$31,317.00
Annabelle Boord	1177	Annabelle Boord	Guatemala	2020-09-16	Corn Popper	negotiation	76	Anna Fali	$30,883.00
Stephaine Vinning	1178	Stephaine Vinning	Australia	2016-05-14	Birite Foodservice Distr	negotiation	43	Xuxue Feng	$93,785.00
Nelida Sawchuk	1179	Nelida Sawchuk	South Africa	2018-06-22	Anchorage Museum Of Hist & Art	qualified	58	Onyama Limba	$68,380.00
Marguerita Hiatt	1180	Marguerita Hiatt	United Kingdom	2018-10-25	Haber, George D Md	qualified	72	Anna Fali	$93,454.00
Carmela Cookey	1181	Carmela Cookey	France	2018-07-19	Royal Pontiac Olds Inc	negotiation	24	Xuxue Feng	$30,570.00
Junita Brideau	1182	Junita Brideau	Indonesia	2015-03-15	Leonards Antiques Inc	negotiation	86	Anna Fali	$79,506.00
Claribel Varriano	1183	Claribel Varriano	Ecuador	2017-04-14	Meca	unqualified	15	Onyama Limba	$8,654.00
Benton Skursky	1184	Benton Skursky	Iceland	2015-02-19	Nercon Engineering & Mfg Inc	negotiation	9	Asiya Javayant	$13,368.00
Hillary Skulski	1185	Hillary Skulski	France	2016-03-25	Replica I	unqualified	82	Bernardo Dominic	$92,631.00
Merilyn Bayless	1186	Merilyn Bayless	Jamaica	2020-10-13	20 20 Printing Inc	unqualified	13	Ivan Magalhaes	$4,989.00
Teri Ennaco	1187	Teri Ennaco	Pakistan	2019-12-21	Publishers Group West	unqualified	57	Bernardo Dominic	$77,668.00
Merlyn Lawler	1188	Merlyn Lawler	Germany	2016-02-26	Nischwitz, Jeffrey L Esq	renewal	45	Ivan Magalhaes	$3,525.00
Georgene Montezuma	1189	Georgene Montezuma	Senegal	2018-10-11	Payne Blades & Wellborn Pa	new	64	Elwin Sharvill	$45,838.00
Jettie Mconnell	1190	Jettie Mconnell	Denmark	2015-10-18	Coldwell Bnkr Wright Real Est	negotiation	74	Ivan Magalhaes	$49,148.00
Lemuel Latzke	1191	Lemuel Latzke	Colombia	2016-02-13	Computer Repair Service	negotiation	79	Stephen Shaw	$96,709.00
Melodie Knipp	1192	Melodie Knipp	Finland	2018-03-08	Fleetwood Building Block Inc	negotiation	19	Asiya Javayant	$23,253.00
Candida Corbley	1193	Candida Corbley	Poland	2017-12-02	Colts Neck Medical Assocs Inc	negotiation	11	Onyama Limba	$40,836.00
Karan Karpin	1194	Karan Karpin	Estonia	2019-01-07	New England Taxidermy	negotiation	4	Stephen Shaw	$60,719.00
Andra Scheyer	1195	Andra Scheyer	Romania	2016-08-14	Ludcke, George O Esq	qualified	62	Elwin Sharvill	$17,419.00
Felicidad Poullion	1196	Felicidad Poullion	Greece	2016-03-05	Mccorkle, Tom S Esq	renewal	64	Elwin Sharvill	$94,052.00
Belen Strassner	1197	Belen Strassner	Ivory Coast	2015-12-14	Eagle Software Inc	qualified	91	Xuxue Feng	$54,241.00
Gracia Melnyk	1198	Gracia Melnyk	Costa Rica	2019-06-01	Juvenile & Adult Super	unqualified	40	Asiya Javayant	$87,668.00
Jolanda Hanafan	1199	Jolanda Hanafan	Cameroon	2015-12-09	Perez, Joseph J Esq	qualified	27	Ivan Magalhaes	$99,417.00

<ToggleButton v-model="balanceFrozen" onIcon="pi pi-lock" offIcon="pi pi-lock-open" onLabel="Balance" offLabel="Balance" />
<DataTable :value="customers" scrollable scrollHeight="400px" class="mt-6">
    <Column field="name" header="Name" style="min-width: 200px" frozen class="font-bold"></Column>
    <Column field="id" header="Id" style="min-width: 100px"></Column>
    <Column field="name" header="Name" style="min-width: 200px"></Column>
    <Column field="country.name" header="Country" style="min-width: 200px"></Column>
    <Column field="date" header="Date" style="min-width: 200px"></Column>
    <Column field="company" header="Company" style="min-width: 200px"></Column>
    <Column field="status" header="Status" style="min-width: 200px"></Column>
    <Column field="activity" header="Activity" style="min-width: 200px"></Column>
    <Column field="representative.name" header="Representative" style="min-width: 200px"></Column>
    <Column field="balance" header="Balance" style="min-width: 200px" alignFrozen="right" :frozen="balanceFrozen">
        <template #body="{ data }">
            <span class="font-bold">{{ formatCurrency(data.balance) }}</span>
        </template>
    </Column>
</DataTable>

Virtual Scroll
Preload
Virtual Scrolling is an efficient way to render large amount data. Usage is similar to regular scrolling with the addition of virtualScrollerOptions property to define a fixed itemSize. Internally, VirtualScroller component is utilized so refer to the API of VirtualScroller for more information about the available options.

In this example, 100000 preloaded records are rendered by the Table.

Id
Vin
Year
Brand
Color
11	VZ1s9	2013	Dover	Green
12	uYSmu	2010	Titan	Black
13	RCnPe	2007	Titan	Red
14	tyuYI	2005	Titan	Silver
15	KJUol	2005	Morello	Red
16	b1qgE	2005	Morello	Red
17	AZF5M	2013	Dover	Silver
18	2hds8	2004	Ibex	Yellow
19	IQKuR	2000	Carson	Yellow
20	p3Gxa	2004	Akira	Red
21	uRjIS	2009	Morello	Yellow
22	x1FCg	2012	Akira	Silver
23	h2uGb	2001	Akira	Silver
24	bth2u	2002	Morello	Silver
25	DGBTr	2003	Vapid	Blue
26	qRlGr	2006	Carson	Blue
27	JwO9L	2010	Ibex	White
28	Deove	2010	Kitano	Red
29	a2ASQ	2013	Norma	Black
30	FNPsi	2003	Ibex	Yellow
31	M4uZm	2001	Ibex	Red
32	TVHBZ	2008	Vapid	Blue
33	zZ5Kc	2017	Norma	Black
34	cdleJ	2009	Morello	Yellow
35	1jUuR	2004	Vapid	Green
36	hduZa	2015	Titan	Yellow

<DataTable :value="cars" scrollable scrollHeight="400px" :virtualScrollerOptions="{ itemSize: 44 }" tableStyle="min-width: 50rem">
    <Column field="id" header="Id" style="width: 20%; height: 44px"></Column>
    <Column field="vin" header="Vin" style="width: 20%; height: 44px"></Column>
    <Column field="year" header="Year" style="width: 20%; height: 44px"></Column>
    <Column field="brand" header="Brand" style="width: 20%; height: 44px"></Column>
    <Column field="color" header="Color" style="width: 20%; height: 44px"></Column>
</DataTable>

Lazy
When lazy loading is enabled via the virtualScrollerOptions, data is fetched on demand during scrolling instead of preload.

In sample below, an in-memory list and timeout is used to mimic fetching from a remote datasource. The virtualCars is an empty array that is populated on scroll.

Id
Vin
Year
Brand
Color
12	77sSg	2004	Akira	Silver
13	J9vjE	2014	Norma	Green
14	aYWVu	2018	Vapid	Silver
15	tlX3u	2004	Carson	Green
16	W9YD7	2009	Norma	Red
17	xLVVW	2016	Dabver	Red
18	0k4Yu	2000	Dabver	White
19	gAdBk	2008	Morello	Black
20	2jaYk	2002	Dover	Black
21	FO2xI	2012	Morello	Blue
22	vZkQ2	2016	Ibex	Red
23	v3srv	2014	Ibex	White
24	nRIMR	2011	Morello	Green
25	CVSZ0	2015	Kitano	Blue
26	fVwHa	2009	Kitano	Yellow
27	hep6y	2010	Akira	Silver
28	aHeyc	2006	Titan	Black
29	0eiBb	2014	Vapid	Yellow
30	ECGfE	2014	Titan	Silver
31	9p3Jp	2008	Dabver	Blue
32	y4LiE	2007	Ibex	Blue
33	pU16k	2016	Ibex	Red
34	EQhme	2016	Ibex	Blue
35	PMCIP	2016	Dabver	Red
36	vCWKL	2012	Dabver	Yellow
37	0iEDG	2011	Carson	Red
38	8khub	2018	Akira	Silver
39	jNVEq	2009	Kitano	Black
40	1E9fA	2017	Dover	Green
41	k18tg	2005	Dover	Silver
42	IS2VZ	2015	Norma	Blue
43	pgM3u	2018	Titan	Silver
44	HqGj4	2004	Dover	Green
45	hmSlc	2015	Dabver	Blue
46	gxrDM	2010	Akira	Green
47	vlWwA	2018	Carson	Yellow
48	OI8MN	2001	Dover	Silver
49	4getM	2002	Dover	Black
50	wkjNH	2006	Ibex	Yellow
51	WXrWq	2016	Norma	Yellow
52	b9TC1	2001	Carson	Silver

<DataTable :value="virtualCars" scrollable scrollHeight="400px" tableStyle="min-width: 50rem"
        :virtualScrollerOptions="{ lazy: true, onLazyLoad: loadCarsLazy, itemSize: 44, delay: 200, showLoader: true, loading: lazyLoading, numToleratedItems: 10 }">
    <Column field="id" header="Id" style="width: 20%; height: 44px">
        <template #loading>
            <div class="flex items-center" :style="{ height: '17px', 'flex-grow': '1', overflow: 'hidden' }">
                <Skeleton width="60%" height="1rem" />
            </div>
        </template>
    </Column>
    <Column field="vin" header="Vin" style="width: 20%; height: 44px">
        <template #loading>
            <div class="flex items-center" :style="{ height: '17px', 'flex-grow': '1', overflow: 'hidden' }">
                <Skeleton width="40%" height="1rem" />
            </div>
        </template>
    </Column>
    <Column field="year" header="Year" style="width: 20%; height: 44px">
        <template #loading>
            <div class="flex items-center" :style="{ height: '17px', 'flex-grow': '1', overflow: 'hidden' }">
                <Skeleton width="30%" height="1rem" />
            </div>
        </template>
    </Column>
    <Column field="brand" header="Brand" style="width: 20%; height: 44px">
        <template #loading>
            <div class="flex items-center" :style="{ height: '17px', 'flex-grow': '1', overflow: 'hidden' }">
                <Skeleton width="40%" height="1rem" />
            </div>
        </template>
    </Column>
    <Column field="color" header="Color" style="width: 20%; height: 44px">
        <template #loading>
            <div class="flex items-center" :style="{ height: '17px', 'flex-grow': '1', overflow: 'hidden' }">
                <Skeleton width="60%" height="1rem" />
            </div>
        </template>
    </Column>
</DataTable>

Column Group
Columns can be grouped within a Row component and groups can be displayed within a ColumnGroup component. These groups can be displayed using type property that can be header or footer. Number of cells and rows to span are defined with the colspan and rowspan properties of a Column.

Product
Sale Rate
Sales
Profits
Bamboo Watch	51%	40%	$54,406.00	$43,342.00
Black Watch	83%	9%	$423,132.00	$312,122.00
Blue Band	38%	5%	$12,321.00	$8,500.00
Blue T-Shirt	49%	22%	$745,232.00	$65,323.00
Brown Purse	17%	79%	$643,242.00	$500,332.00
Chakra Bracelet	52%	65%	$421,132.00	$150,005.00
Galaxy Earrings	82%	12%	$131,211.00	$100,214.00
Game Controller	44%	45%	$66,442.00	$53,322.00
Gaming Set	90%	56%	$765,442.00	$296,232.00
Gold Phone Case	75%	54%	$21,212.00	$12,533.00
Totals:	$3,283,772.00	$1,541,925.00

<DataTable :value="sales" tableStyle="min-width: 50rem">
    <ColumnGroup type="header">
        <Row>
            <Column header="Product" :rowspan="3" />
            <Column header="Sale Rate" :colspan="4" />
        </Row>
        <Row>
            <Column header="Sales" :colspan="2" />
            <Column header="Profits" :colspan="2" />
        </Row>
        <Row>
            <Column header="Last Year" sortable field="lastYearSale" />
            <Column header="This Year" sortable field="thisYearSale" />
            <Column header="Last Year" sortable field="lastYearProfit" />
            <Column header="This Year" sortable field="thisYearProfit" />
        </Row>
    </ColumnGroup>
    <Column field="product" />
    <Column field="lastYearSale">
        <template #body="slotProps"> {{ slotProps.data.lastYearSale }}% </template>
    </Column>
    <Column field="thisYearSale">
        <template #body="slotProps"> {{ slotProps.data.thisYearSale }}% </template>
    </Column>
    <Column field="lastYearProfit">
        <template #body="slotProps">
            {{ formatCurrency(slotProps.data.lastYearProfit) }}
        </template>
    </Column>
    <Column field="thisYearProfit">
        <template #body="slotProps">
            {{ formatCurrency(slotProps.data.thisYearProfit) }}
        </template>
    </Column>
    <ColumnGroup type="footer">
        <Row>
            <Column footer="Totals:" :colspan="3" footerStyle="text-align:right" />
            <Column :footer="lastYearTotal" />
            <Column :footer="thisYearTotal" />
        </Row>
    </ColumnGroup>
</DataTable>

Row Group
Subheader
Rows are grouped with the groupRowsBy property. When rowGroupMode is set as subheader, a header and footer can be displayed for each group. The content of a group header is provided with groupheader and footer with groupfooter slots.

Name
Country
Company
Status
Date
Amy Elsner
Amy Elsner
Josephine Darakjy	
flag
Egypt
Chanay, Jeffrey A Esq	
negotiation
2019-02-09
Graciela Ruta	
flag
Chile
Buckley Miller & Wright	
negotiation
2016-07-25
Ezekiel Chui	
flag
Ireland
Sider, Donald C Esq	
new
2016-09-24
Jose Stockham	
flag
Italy
Tri State Refueler Co	
qualified
2018-04-25
Rozella Ostrosky	
flag
Venezuela
Parkway Company	
unqualified
2016-02-27
Dyan Oldroyd	
flag
Argentina
International Eyelets Inc	
qualified
2017-02-02
Erick Ferencz	
flag
Belgium
Cindy Turner Associates	
unqualified
2018-05-06
Total Customers: 7
Anna Fali
Anna Fali
Minna Amigon	
flag
Romania
Dorl, James J Esq	
qualified
2018-11-07
Mattie Poquette	
flag
Venezuela
Century Communications	
negotiation
2017-12-12
Roxane Campain	
flag
France
Rapid Trading Intl	
unqualified
2018-12-25
Total Customers: 3
Asiya Javayant
Asiya Javayant
Art Venere	
flag
Panama
Chemel, James L Cpa	
qualified
2017-05-13
Donette Foller	
flag
South Africa
Printing Dimensions	
negotiation
2016-05-20
Cammy Albares	
flag
Philippines
Rousseaux, Michael Esq	
new
2019-06-25
Willard Kolmetz	
flag
Tunisia
Ingalls, Donald R Esq	
renewal
2017-04-15
Ammie Corrio	
flag
Hungary
Moskowitz, Barry S	
negotiation
2016-06-11
Blair Malet	
flag
Finland
Bollinger Mach Shp & Shipyard	
new
2018-04-19
Total Customers: 6
Bernardo Dominic
Bernardo Dominic
Abel Maclead	
flag
Singapore
Rangoni Of Florence	
qualified
2017-03-11
Yuki Whobrey	
flag
Israel
Farmers Insurance Group	
negotiation
2017-12-21
Albina Glick	
flag
Ukraine
Giampetro, Anthony D	
negotiation
2019-08-08
Valentine Gillian	
flag
Paraguay
Fbs Business Finance	
qualified
2019-09-17
Total Customers: 4
Elwin Sharvill
Elwin Sharvill
Maryann Royster	
flag
Belarus
Franklin, Peter L Esq	
qualified
2017-03-11
Total Customers: 1
Ioni Bowcher
Ioni Bowcher
James Butt	
flag
Algeria
Benton, John B Jr	
unqualified
2015-09-13
Veronika Inouye	
flag
Ecuador
C 4 Network Inc	
renewal
2017-03-24
Chanel Caudy	
flag
Argentina
Professional Image Inc	
new
2018-06-24
Bernardo Figeroa	
flag
Israel
Clark, Richard Cpa	
renewal
2018-04-11
Francine Vocelka	
flag
Honduras
Cascade Realty Advisors Inc	
qualified
2017-08-02
Kati Rulapaugh	
flag
Puerto Rico
Eder Assocs Consltng Engrs Pc	
renewal
2016-12-03
Total Customers: 6
Ivan Magalhaes
Ivan Magalhaes
Simona Morasca	
flag
Egypt
Chapman, Ross E Esq	
qualified
2018-02-16
Mitsue Tollner	
flag
Paraguay
Morlong Associates	
renewal
2018-02-19
Sage Wieser	
flag
Egypt
Truhlar And Truhlar Attys	
unqualified
2018-11-21
Meaghan Garufi	
flag
Malaysia
Bolton, Wilbur Esq	
unqualified
2018-07-04
Allene Iturbide	
flag
Italy
Ledecky, David Esq	
qualified
2016-02-20
Alishia Sergi	
flag
Qatar
Milford Enterprises Inc	
negotiation
2018-05-19
Total Customers: 6
Onyama Limba
Onyama Limba
Leota Dilliard	
flag
Serbia
Commercial Press	
renewal
2019-08-13
Kris Marrier	
flag
Mexico
King, Christopher A Esq	
negotiation
2015-07-07
Kiley Caldarera	
flag
Serbia
Feiner Bros	
unqualified
2015-10-20
Bette Nicka	
flag
Paraguay
Sport En Art	
renewal
2016-10-21
Willow Kusko	
flag
Romania
U Pull It	
qualified
2020-04-11
Solange Shinko	
flag
Cameroon
Mosocco, Ronald A	
qualified
2015-02-12
Fatima Saylors	
flag
Canada
Stanton, James D Esq	
renewal
2019-07-10
Total Customers: 7
Stephen Shaw
Stephen Shaw
Gladys Rim	
flag
Netherlands
T M Byxbee Company Pc	
renewal
2020-02-27
Alisha Slusarski	
flag
Iceland
Wtlz Power 107 Fm	
qualified
2018-03-27
Lavera Perin	
flag
Vietnam
Abc Enterprises Inc	
qualified
2018-04-10
Emerson Bowley	
flag
Finland
Knights Inn	
new
2018-11-24
Total Customers: 4
Xuxue Feng
Xuxue Feng
Lenna Paprocki	
flag
Slovenia
Feltz Printing Service	
new
2020-09-15
Fletcher Flosi	
flag
Argentina
Post Box Services Plus	
renewal
2016-01-04
Ernie Stenseth	
flag
Australia
Knwz Newsradio	
renewal
2018-06-06
Youlanda Schemmer	
flag
Bolivia
Tri M Tool Inc	
negotiation
2017-12-15
Jina Briddick	
flag
Mexico
Grace Pastries Inc	
unqualified
2018-02-19
Kanisha Waycott	
flag
Ecuador
Schroer, Gene E Esq	
new
2019-11-27
Total Customers: 6

<DataTable :value="customers" rowGroupMode="subheader" groupRowsBy="representative.name" sortMode="single"
        sortField="representative.name" :sortOrder="1" scrollable scrollHeight="400px" tableStyle="min-width: 50rem">
    <Column field="representative.name" header="Representative"></Column>
    <Column field="name" header="Name" style="min-width: 200px"></Column>
    <Column field="country" header="Country" style="min-width: 200px">
        <template #body="slotProps">
            <div class="flex items-center gap-2">
                <img alt="flag" src="https://primefaces.org/cdn/primevue/images/flag/flag_placeholder.png" :class="`flag flag-${slotProps.data.country.code}`" style="width: 24px" />
                <span>{{ slotProps.data.country.name }}</span>
            </div>
        </template>
    </Column>
    <Column field="company" header="Company" style="min-width: 200px"></Column>
    <Column field="status" header="Status" style="min-width: 200px">
        <template #body="slotProps">
            <Tag :value="slotProps.data.status" :severity="getSeverity(slotProps.data.status)" />
        </template>
    </Column>
    <Column field="date" header="Date" style="min-width: 200px"></Column>
    <template #groupheader="slotProps">
        <div class="flex items-center gap-2">
            <img :alt="slotProps.data.representative.name" :src="`https://primefaces.org/cdn/primevue/images/avatar/${slotProps.data.representative.image}`" width="32" style="vertical-align: middle" />
            <span>{{ slotProps.data.representative.name }}</span>
        </div>
    </template>
    <template #groupfooter="slotProps">
        <div class="flex justify-end font-bold w-full">Total Customers: {{ calculateCustomerTotal(slotProps.data.representative.name) }}</div>
    </template>
</DataTable>

Expandable
When expandableRowGroups is present in subheader based row grouping, groups can be expanded and collapsed. State of the expansions are controlled using the expandedRows property and rowgroup-expand and rowgroup-collapse events.

Name
Country
Company
Status
Date
Amy ElsnerAmy Elsner
Anna FaliAnna Fali
Asiya JavayantAsiya Javayant
Bernardo DominicBernardo Dominic
Elwin SharvillElwin Sharvill
Ioni BowcherIoni Bowcher
Ivan MagalhaesIvan Magalhaes
Onyama LimbaOnyama Limba
Stephen ShawStephen Shaw
Xuxue FengXuxue Feng

<DataTable v-model:expandedRowGroups="expandedRowGroups" :value="customers" tableStyle="min-width: 50rem"
        expandableRowGroups rowGroupMode="subheader" groupRowsBy="representative.name" @rowgroup-expand="onRowGroupExpand" @rowgroup-collapse="onRowGroupCollapse"
        sortMode="single" sortField="representative.name" :sortOrder="1">
    <template #groupheader="slotProps">
        <img :alt="slotProps.data.representative.name" :src="`https://primefaces.org/cdn/primevue/images/avatar/${slotProps.data.representative.image}`" width="32" style="vertical-align: middle; display: inline-block" class="ml-2" />
        <span class="align-middle ml-2 font-bold leading-normal">{{ slotProps.data.representative.name }}</span>
    </template>
    <Column field="representative.name" header="Representative"></Column>
    <Column field="name" header="Name" style="width: 20%"></Column>
    <Column field="country" header="Country" style="width: 20%">
        <template #body="slotProps">
            <div class="flex items-center gap-2">
                <img alt="flag" src="https://primefaces.org/cdn/primevue/images/flag/flag_placeholder.png" :class="`flag flag-${slotProps.data.country.code}`" style="width: 24px" />
                <span>{{ slotProps.data.country.name }}</span>
            </div>
        </template>
    </Column>
    <Column field="company" header="Company" style="width: 20%"></Column>
    <Column field="status" header="Status" style="width: 20%">
        <template #body="slotProps">
            <Tag :value="slotProps.data.status" :severity="getSeverity(slotProps.data.status)" />
        </template>
    </Column>
    <Column field="date" header="Date" style="width: 20%"></Column>
    <template #groupfooter="slotProps">
        <div class="flex justify-end font-bold w-full">Total Customers: {{ calculateCustomerTotal(slotProps.data.representative.name) }}</div>
    </template>
</DataTable>

RowSpan
When rowGroupMode is configured to be rowspan, the grouping column spans multiple rows.

#
Representative
Name
Country
Company
Status
1	
Amy Elsner
Amy Elsner
Josephine Darakjy	
flag
Egypt
Chanay, Jeffrey A Esq	
negotiation
2	Graciela Ruta	
flag
Chile
Buckley Miller & Wright	
negotiation
3	Ezekiel Chui	
flag
Ireland
Sider, Donald C Esq	
new
4	Jose Stockham	
flag
Italy
Tri State Refueler Co	
qualified
5	Rozella Ostrosky	
flag
Venezuela
Parkway Company	
unqualified
6	Dyan Oldroyd	
flag
Argentina
International Eyelets Inc	
qualified
7	Erick Ferencz	
flag
Belgium
Cindy Turner Associates	
unqualified
8	
Anna Fali
Anna Fali
Minna Amigon	
flag
Romania
Dorl, James J Esq	
qualified
9	Mattie Poquette	
flag
Venezuela
Century Communications	
negotiation
10	Roxane Campain	
flag
France
Rapid Trading Intl	
unqualified
11	
Asiya Javayant
Asiya Javayant
Art Venere	
flag
Panama
Chemel, James L Cpa	
qualified
12	Donette Foller	
flag
South Africa
Printing Dimensions	
negotiation
13	Cammy Albares	
flag
Philippines
Rousseaux, Michael Esq	
new
14	Willard Kolmetz	
flag
Tunisia
Ingalls, Donald R Esq	
renewal
15	Ammie Corrio	
flag
Hungary
Moskowitz, Barry S	
negotiation
16	Blair Malet	
flag
Finland
Bollinger Mach Shp & Shipyard	
new
17	
Bernardo Dominic
Bernardo Dominic
Abel Maclead	
flag
Singapore
Rangoni Of Florence	
qualified
18	Yuki Whobrey	
flag
Israel
Farmers Insurance Group	
negotiation
19	Albina Glick	
flag
Ukraine
Giampetro, Anthony D	
negotiation
20	Valentine Gillian	
flag
Paraguay
Fbs Business Finance	
qualified
21	
Elwin Sharvill
Elwin Sharvill
Maryann Royster	
flag
Belarus
Franklin, Peter L Esq	
qualified
22	
Ioni Bowcher
Ioni Bowcher
James Butt	
flag
Algeria
Benton, John B Jr	
unqualified
23	Veronika Inouye	
flag
Ecuador
C 4 Network Inc	
renewal
24	Chanel Caudy	
flag
Argentina
Professional Image Inc	
new
25	Bernardo Figeroa	
flag
Israel
Clark, Richard Cpa	
renewal
26	Francine Vocelka	
flag
Honduras
Cascade Realty Advisors Inc	
qualified
27	Kati Rulapaugh	
flag
Puerto Rico
Eder Assocs Consltng Engrs Pc	
renewal
28	
Ivan Magalhaes
Ivan Magalhaes
Simona Morasca	
flag
Egypt
Chapman, Ross E Esq	
qualified
29	Mitsue Tollner	
flag
Paraguay
Morlong Associates	
renewal
30	Sage Wieser	
flag
Egypt
Truhlar And Truhlar Attys	
unqualified
31	Meaghan Garufi	
flag
Malaysia
Bolton, Wilbur Esq	
unqualified
32	Allene Iturbide	
flag
Italy
Ledecky, David Esq	
qualified
33	Alishia Sergi	
flag
Qatar
Milford Enterprises Inc	
negotiation
34	
Onyama Limba
Onyama Limba
Leota Dilliard	
flag
Serbia
Commercial Press	
renewal
35	Kris Marrier	
flag
Mexico
King, Christopher A Esq	
negotiation
36	Kiley Caldarera	
flag
Serbia
Feiner Bros	
unqualified
37	Bette Nicka	
flag
Paraguay
Sport En Art	
renewal
38	Willow Kusko	
flag
Romania
U Pull It	
qualified
39	Solange Shinko	
flag
Cameroon
Mosocco, Ronald A	
qualified
40	Fatima Saylors	
flag
Canada
Stanton, James D Esq	
renewal
41	
Stephen Shaw
Stephen Shaw
Gladys Rim	
flag
Netherlands
T M Byxbee Company Pc	
renewal
42	Alisha Slusarski	
flag
Iceland
Wtlz Power 107 Fm	
qualified
43	Lavera Perin	
flag
Vietnam
Abc Enterprises Inc	
qualified
44	Emerson Bowley	
flag
Finland
Knights Inn	
new
45	
Xuxue Feng
Xuxue Feng
Lenna Paprocki	
flag
Slovenia
Feltz Printing Service	
new
46	Fletcher Flosi	
flag
Argentina
Post Box Services Plus	
renewal
47	Ernie Stenseth	
flag
Australia
Knwz Newsradio	
renewal
48	Youlanda Schemmer	
flag
Bolivia
Tri M Tool Inc	
negotiation
49	Jina Briddick	
flag
Mexico
Grace Pastries Inc	
unqualified
50	Kanisha Waycott	
flag
Ecuador
Schroer, Gene E Esq	
new

<DataTable :value="customers" rowGroupMode="rowspan" groupRowsBy="representative.name" sortMode="single" sortField="representative.name" :sortOrder="1" tableStyle="min-width: 50rem">
    <Column header="#" headerStyle="width:3rem">
        <template #body="slotProps">
            {{ slotProps.index + 1 }}
        </template>
    </Column>
    <Column field="representative.name" header="Representative" style="min-width: 200px">
        <template #body="slotProps">
            <div class="flex items-center gap-2">
                <img :alt="slotProps.data.representative.name" :src="`https://primefaces.org/cdn/primevue/images/avatar/${slotProps.data.representative.image}`" width="32" style="vertical-align: middle" />
                <span>{{ slotProps.data.representative.name }}</span>
            </div>
        </template>
    </Column>
    <Column field="name" header="Name" style="min-width: 200px"></Column>
    <Column field="country" header="Country" style="min-width: 150px">
        <template #body="slotProps">
            <div class="flex items-center gap-2">
                <img alt="flag" src="https://primefaces.org/cdn/primevue/images/flag/flag_placeholder.png" :class="`flag flag-${slotProps.data.country.code}`" style="width: 24px" />
                <span>{{ slotProps.data.country.name }}</span>
            </div>
        </template>
    </Column>
    <Column field="company" header="Company" style="min-width: 200px"></Column>
    <Column field="status" header="Status" style="min-width: 100px">
        <template #body="slotProps">
            <Tag :value="slotProps.data.status" :severity="getSeverity(slotProps.data.status)" />
        </template>
    </Column>
</DataTable>

Conditional Style
Particular rows and cells can be styled based on conditions. The rowClass receives a row data as a parameter to return a style class for a row whereas cells are customized using the body template.

Code
Name
Category
Quantity
f230fh0g3	Bamboo Watch	Accessories	24
nvklal433	Black Watch	Accessories	61
zz21cz3c1	Blue Band	Fitness	2
244wgerg2	Blue T-Shirt	Clothing	25
h456wer53	Bracelet	Accessories	73
av2231fwg	Brown Purse	Accessories	0
bib36pfvm	Chakra Bracelet	Accessories	5
mbvjkgip5	Galaxy Earrings	Accessories	23
vbb124btr	Game Controller	Electronics	2
cm230f032	Gaming Set	Electronics	63

<DataTable :value="products" :rowClass="rowClass" :rowStyle="rowStyle" tableStyle="min-width: 50rem">
    <Column field="code" header="Code"></Column>
    <Column field="name" header="Name"></Column>
    <Column field="category" header="Category"></Column>
    <Column field="quantity" header="Quantity">
        <template #body="slotProps">
            <Badge :value="slotProps.data.quantity" :severity="stockSeverity(slotProps.data)" />
        </template>
    </Column>
</DataTable>

Column Resize
Fit Mode
Columns can be resized with drag and drop when resizableColumns is enabled. Default resize mode is fit that does not change the overall table width.

Code
Name
Category
Quantity
f230fh0g3	Bamboo Watch	Accessories	24
nvklal433	Black Watch	Accessories	61
zz21cz3c1	Blue Band	Fitness	2
244wgerg2	Blue T-Shirt	Clothing	25
h456wer53	Bracelet	Accessories	73

<DataTable :value="products" resizableColumns columnResizeMode="fit" showGridlines tableStyle="min-width: 50rem">
    <Column field="code" header="Code"></Column>
    <Column field="name" header="Name"></Column>
    <Column field="category" header="Category"></Column>
    <Column field="quantity" header="Quantity"></Column>
</DataTable>

Expand Mode
Setting columnResizeMode as expand changes the table width as well.

Code
Name
Category
Quantity
f230fh0g3	Bamboo Watch	Accessories	24
nvklal433	Black Watch	Accessories	61
zz21cz3c1	Blue Band	Fitness	2
244wgerg2	Blue T-Shirt	Clothing	25
h456wer53	Bracelet	Accessories	73

<DataTable :value="products" resizableColumns columnResizeMode="expand" showGridlines tableStyle="min-width: 50rem">
    <Column field="code" header="Code"></Column>
    <Column field="name" header="Name"></Column>
    <Column field="category" header="Category"></Column>
    <Column field="quantity" header="Quantity"></Column>
</DataTable>

Reorder
Order of the columns and rows can be changed using drag and drop. Column reordering is configured by adding reorderableColumns property.

Similarly, adding rowReorder property to a column enables draggable rows. For the drag handle a column needs to have rowReorder property and table needs to have row-reorder event is required to control the state of the rows after reorder completes.

Code
Name
Category
Quantity
f230fh0g3	Bamboo Watch	Accessories	24
nvklal433	Black Watch	Accessories	61
zz21cz3c1	Blue Band	Fitness	2
244wgerg2	Blue T-Shirt	Clothing	25
h456wer53	Bracelet	Accessories	73

<DataTable :value="products" :reorderableColumns="true" @columnReorder="onColReorder" @rowReorder="onRowReorder" tableStyle="min-width: 50rem">
    <Column rowReorder headerStyle="width: 3rem" :reorderableColumn="false" />
    <Column v-for="col of columns" :field="col.field" :header="col.header" :key="col.field"></Column>
</DataTable>

Column Toggle
Column visibility based on a condition can be implemented with dynamic columns, in this sample a MultiSelect is used to manage the visible columns.

Code
Name
Category
Quantity
f230fh0g3	Bamboo Watch	Accessories	24
nvklal433	Black Watch	Accessories	61
zz21cz3c1	Blue Band	Fitness	2
244wgerg2	Blue T-Shirt	Clothing	25
h456wer53	Bracelet	Accessories	73

<DataTable :value="products" tableStyle="min-width: 50rem">
    <template #header>
        <div style="text-align:left">
            <MultiSelect :modelValue="selectedColumns" :options="columns" optionLabel="header" @update:modelValue="onToggle"
                display="chip" placeholder="Select Columns" />
        </div>
    </template>
    <Column field="code" header="Code" />
    <Column v-for="(col, index) of selectedColumns" :field="col.field" :header="col.header" :key="col.field + '_' + index"></Column>
</DataTable>

Export
DataTable can export its data to CSV format.

Code
Name
Category
Quantity
f230fh0g3	Bamboo Watch	Accessories	24
nvklal433	Black Watch	Accessories	61
zz21cz3c1	Blue Band	Fitness	2
244wgerg2	Blue T-Shirt	Clothing	25
h456wer53	Bracelet	Accessories	73

<DataTable :value="products" ref="dt" tableStyle="min-width: 50rem">
    <template #header>
        <div class="text-end pb-4">
            <Button icon="pi pi-external-link" label="Export" @click="exportCSV($event)" />
        </div>
    </template>
    <Column field="code" header="Code" exportHeader="Product Code"></Column>
    <Column field="name" header="Name"></Column>
    <Column field="category" header="Category"></Column>
    <Column field="quantity" header="Quantity"></Column>
</DataTable>

Context Menu
DataTable has exclusive integration with ContextMenu using the contextMenu event to open a menu on right click along with contextMenuSelection property and row-contextmenu event to control the selection via the menu.

Code
Name
Category
Price
f230fh0g3	Bamboo Watch	Accessories	$65.00
nvklal433	Black Watch	Accessories	$72.00
zz21cz3c1	Blue Band	Fitness	$79.00
244wgerg2	Blue T-Shirt	Clothing	$29.00
h456wer53	Bracelet	Accessories	$15.00

<ContextMenu ref="cm" :model="menuModel" @hide="selectedProduct = null" />
<DataTable v-model:contextMenuSelection="selectedProduct" :value="products" contextMenu
        @row-contextmenu="onRowContextMenu" tableStyle="min-width: 50rem">
    <Column field="code" header="Code"></Column>
    <Column field="name" header="Name"></Column>
    <Column field="category" header="Category"></Column>
    <Column field="price" header="Price">
        <template #body="slotProps">
            {{ formatCurrency(slotProps.data.price) }}
        </template>
    </Column>
</DataTable>

Stateful
Stateful table allows keeping the state such as page, sort and filtering either at local storage or session storage so that when the page is visited again, table would render the data using the last settings.

Change the state of the table e.g paginate, navigate away and then return to this table again to test this feature, the setting is set as session with the stateStorage property so that Table retains the state until the browser is closed. Other alternative is local referring to localStorage for an extended lifetime.

Global Search
James Butt	
flag
Algeria
Ioni Bowcher
Ioni Bowcher
unqualified
Josephine Darakjy	
flag
Egypt
Amy Elsner
Amy Elsner
negotiation
Art Venere	
flag
Panama
Asiya Javayant
Asiya Javayant
qualified
Lenna Paprocki	
flag
Slovenia
Xuxue Feng
Xuxue Feng
new
Donette Foller	
flag
South Africa
Asiya Javayant
Asiya Javayant
negotiation

<DataTable v-model:filters="filters" v-model:selection="selectedCustomer" :value="customers"
    stateStorage="session" stateKey="dt-state-demo-session" paginator :rows="5" filterDisplay="menu"
    selectionMode="single" dataKey="id" :globalFilterFields="['name', 'country.name', 'representative.name', 'status']" tableStyle="min-width: 50rem">
    <template #header>
        <IconField>
            <InputIcon>
                <i class="pi pi-search" />
            </InputIcon>
            <InputText v-model="filters['global'].value" placeholder="Global Search" />
        </IconField>
    </template>
    <Column field="name" header="Name" sortable style="width: 25%">
        <template #filter="{ filterModel }">
            <InputText v-model="filterModel.value" type="text" placeholder="Search by name" />
        </template>
    </Column>
    <Column header="Country" sortable sortField="country.name" filterField="country.name" filterMatchMode="contains" style="width: 25%">
        <template #body="{ data }">
            <div class="flex items-center gap-2">
                <img alt="flag" src="https://primefaces.org/cdn/primevue/images/flag/flag_placeholder.png" :class="`flag flag-${data.country.code}`" style="width: 24px" />
                <span>{{ data.country.name }}</span>
            </div>
        </template>
        <template #filter="{ filterModel }">
            <InputText v-model="filterModel.value" type="text" placeholder="Search by country" />
        </template>
    </Column>
    <Column header="Representative" sortable sortField="representative.name" filterField="representative" :showFilterMatchModes="false" :filterMenuStyle="{ width: '14rem' }" style="width: 25%">
        <template #body="{ data }">
            <div class="flex items-center gap-2">
                <img :alt="data.representative.name" :src="`https://primefaces.org/cdn/primevue/images/avatar/${data.representative.image}`" style="width: 32px" />
                <span>{{ data.representative.name }}</span>
            </div>
        </template>
        <template #filter="{ filterModel }">
            <MultiSelect v-model="filterModel.value" :options="representatives" optionLabel="name" placeholder="Any">
                <template #option="slotProps">
                    <div class="flex items-center gap-2">
                        <img :alt="slotProps.option.name" :src="`https://primefaces.org/cdn/primevue/images/avatar/${slotProps.option.image}`" style="width: 32px" />
                        <span>{{ slotProps.option.name }}</span>
                    </div>
                </template>
            </MultiSelect>
        </template>
    </Column>
    <Column field="status" header="Status" sortable filterMatchMode="equals" style="width: 25%">
        <template #body="{ data }">
            <Tag :value="data.status" :severity="getSeverity(data.status)" />
        </template>
        <template #filter="{ filterModel }">
            <Select v-model="filterModel.value" :options="statuses" placeholder="Select One" showClear>
                <template #option="slotProps">
                    <Tag :value="slotProps.option" :severity="getSeverity(slotProps.option)" />
                </template>
            </Select>
        </template>
    </Column>
    <template #empty> No customers found. </template>
</DataTable>

Samples
Customers
DataTable with selection, pagination, filtering, sorting and templating.

Keyword Search
James Butt	
flag
Algeria
Ioni Bowcher
Ioni Bowcher
13.09.2015	$70,663.00	
unqualified
Josephine Darakjy	
flag
Egypt
Amy Elsner
Amy Elsner
09.02.2019	$82,429.00	
negotiation
Art Venere	
flag
Panama
Asiya Javayant
Asiya Javayant
13.05.2017	$28,334.00	
qualified
Lenna Paprocki	
flag
Slovenia
Xuxue Feng
Xuxue Feng
15.09.2020	$88,521.00	
new
Donette Foller	
flag
South Africa
Asiya Javayant
Asiya Javayant
20.05.2016	$93,905.00	
negotiation
Simona Morasca	
flag
Egypt
Ivan Magalhaes
Ivan Magalhaes
16.02.2018	$50,041.00	
qualified
Mitsue Tollner	
flag
Paraguay
Ivan Magalhaes
Ivan Magalhaes
19.02.2018	$58,706.00	
renewal
Leota Dilliard	
flag
Serbia
Onyama Limba
Onyama Limba
13.08.2019	$26,640.00	
renewal
Sage Wieser	
flag
Egypt
Ivan Magalhaes
Ivan Magalhaes
21.11.2018	$65,369.00	
unqualified
Kris Marrier	
flag
Mexico
Onyama Limba
Onyama Limba
07.07.2015	$63,451.00	
negotiation

<DataTable v-model:filters="filters" v-model:selection="selectedCustomers" :value="customers" paginator :rows="10" dataKey="id" filterDisplay="menu"
    :globalFilterFields="['name', 'country.name', 'representative.name', 'balance', 'status']">
    <template #header>
        <div class="flex justify-between">
            <Button type="button" icon="pi pi-filter-slash" label="Clear" variant="outlined" @click="clearFilter()" />
            <IconField>
                <InputIcon>
                    <i class="pi pi-search" />
                </InputIcon>
                <InputText v-model="filters['global'].value" placeholder="Keyword Search" />
            </IconField>
        </div>
    </template>
    <template #empty> No customers found. </template>
    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
    <Column field="name" header="Name" sortable style="min-width: 14rem">
        <template #body="{ data }">
            {{ data.name }}
        </template>
        <template #filter="{ filterModel }">
            <InputText v-model="filterModel.value" type="text" placeholder="Search by name" />
        </template>
    </Column>
    <Column header="Country" sortable sortField="country.name" filterField="country.name" style="min-width: 14rem">
        <template #body="{ data }">
            <div class="flex items-center gap-2">
                <img alt="flag" src="https://primefaces.org/cdn/primevue/images/flag/flag_placeholder.png" :class="`flag flag-${data.country.code}`" style="width: 24px" />
                <span>{{ data.country.name }}</span>
            </div>
        </template>
        <template #filter="{ filterModel }">
            <InputText v-model="filterModel.value" type="text" placeholder="Search by country" />
        </template>
    </Column>
    <Column header="Agent" sortable sortField="representative.name" filterField="representative" :showFilterMatchModes="false" :filterMenuStyle="{ width: '14rem' }" style="min-width: 14rem">
        <template #body="{ data }">
            <div class="flex items-center gap-2">
                <img :alt="data.representative.name" :src="`https://primefaces.org/cdn/primevue/images/avatar/${data.representative.image}`" style="width: 32px" />
                <span>{{ data.representative.name }}</span>
            </div>
        </template>
        <template #filter="{ filterModel }">
            <MultiSelect v-model="filterModel.value" :options="representatives" optionLabel="name" placeholder="Any">
                <template #option="slotProps">
                    <div class="flex items-center gap-2">
                        <img :alt="slotProps.option.name" :src="`https://primefaces.org/cdn/primevue/images/avatar/${slotProps.option.image}`" style="width: 32px" />
                        <span>{{ slotProps.option.name }}</span>
                    </div>
                </template>
            </MultiSelect>
        </template>
    </Column>
    <Column field="date" header="Date" sortable filterField="date" dataType="date" style="min-width: 10rem">
        <template #body="{ data }">
            {{ formatDate(data.date) }}
        </template>
        <template #filter="{ filterModel }">
            <DatePicker v-model="filterModel.value" dateFormat="mm/dd/yy" placeholder="mm/dd/yyyy" />
        </template>
    </Column>
    <Column field="balance" header="Balance" sortable filterField="balance" dataType="numeric" style="min-width: 10rem">
        <template #body="{ data }">
            {{ formatCurrency(data.balance) }}
        </template>
        <template #filter="{ filterModel }">
            <InputNumber v-model="filterModel.value" mode="currency" currency="USD" locale="en-US" />
        </template>
    </Column>
    <Column header="Status" field="status" sortable :filterMenuStyle="{ width: '14rem' }" style="min-width: 12rem">
        <template #body="{ data }">
            <Tag :value="data.status" :severity="getSeverity(data.status)" />
        </template>
        <template #filter="{ filterModel }">
            <Select v-model="filterModel.value" :options="statuses" placeholder="Select One" showClear>
                <template #option="slotProps">
                    <Tag :value="slotProps.option" :severity="getSeverity(slotProps.option)" />
                </template>
            </Select>
        </template>
    </Column>
    <Column field="activity" header="Activity" sortable :showFilterMatchModes="false" style="min-width: 12rem">
        <template #body="{ data }">
            <ProgressBar :value="data.activity" :showValue="false" style="height: 6px"></ProgressBar>
        </template>
        <template #filter="{ filterModel }">
            <Slider v-model="filterModel.value" range class="m-4"></Slider>
            <div class="flex items-center justify-between px-2">
                <span>{{ filterModel.value ? filterModel.value[0] : 0 }}</span>
                <span>{{ filterModel.value ? filterModel.value[1] : 100 }}</span>
            </div>
        </template>
    </Column>
    <Column headerStyle="width: 5rem; text-align: center" bodyStyle="text-align: center; overflow: visible">
        <template #body>
            <Button type="button" icon="pi pi-cog" rounded />
        </template>
    </Column>
</DataTable>

Products
CRUD implementation example with a Dialog.

Manage Products
Search...
Image
f230fh0g3	Bamboo Watch	bamboo-watch.jpg	$65.00	Accessories	




	
INSTOCK
nvklal433	Black Watch	black-watch.jpg	$72.00	Accessories	




	
INSTOCK
zz21cz3c1	Blue Band	blue-band.jpg	$79.00	Fitness	




	
LOWSTOCK
244wgerg2	Blue T-Shirt	blue-t-shirt.jpg	$29.00	Clothing	




	
INSTOCK
h456wer53	Bracelet	bracelet.jpg	$15.00	Accessories	




	
INSTOCK
av2231fwg	Brown Purse	brown-purse.jpg	$120.00	Accessories	




	
OUTOFSTOCK
bib36pfvm	Chakra Bracelet	chakra-bracelet.jpg	$32.00	Accessories	




	
LOWSTOCK
mbvjkgip5	Galaxy Earrings	galaxy-earrings.jpg	$34.00	Accessories	




	
INSTOCK
vbb124btr	Game Controller	game-controller.jpg	$99.00	Electronics	




	
LOWSTOCK
cm230f032	Gaming Set	gaming-set.jpg	$299.00	Electronics	




	
INSTOCK
Showing 1 to 10 of 30 products

<Toolbar class="mb-6">
    <template #start>
        <Button label="New" icon="pi pi-plus" class="mr-2" @click="openNew" />
        <Button label="Delete" icon="pi pi-trash" severity="danger" variant="outlined" @click="confirmDeleteSelected" :disabled="!selectedProducts || !selectedProducts.length" />
    </template>

    <template #end>
        <FileUpload mode="basic" accept="image/*" :maxFileSize="1000000" label="Import" customUpload chooseLabel="Import" class="mr-2" auto :chooseButtonProps="{ severity: 'secondary' }" />
        <Button label="Export" icon="pi pi-upload" severity="secondary" @click="exportCSV($event)" />
    </template>
</Toolbar>

<DataTable
    ref="dt"
    v-model:selection="selectedProducts"
    :value="products"
    dataKey="id"
    :paginator="true"
    :rows="10"
    :filters="filters"
    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
    :rowsPerPageOptions="[5, 10, 25]"
    currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products"
>
    <template #header>
        <div class="flex flex-wrap gap-2 items-center justify-between">
            <h4 class="m-0">Manage Products</h4>
            <IconField>
                <InputIcon>
                    <i class="pi pi-search" />
                </InputIcon>
                <InputText v-model="filters['global'].value" placeholder="Search..." />
            </IconField>
        </div>
    </template>

    <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
    <Column field="code" header="Code" sortable style="min-width: 12rem"></Column>
    <Column field="name" header="Name" sortable style="min-width: 16rem"></Column>
    <Column header="Image">
        <template #body="slotProps">
            <img :src="`https://primefaces.org/cdn/primevue/images/product/${slotProps.data.image}`" :alt="slotProps.data.image" class="rounded" style="width: 64px" />
        </template>
    </Column>
    <Column field="price" header="Price" sortable style="min-width: 8rem">
        <template #body="slotProps">
            {{ formatCurrency(slotProps.data.price) }}
        </template>
    </Column>
    <Column field="category" header="Category" sortable style="min-width: 10rem"></Column>
    <Column field="rating" header="Reviews" sortable style="min-width: 12rem">
        <template #body="slotProps">
            <Rating :modelValue="slotProps.data.rating" :readonly="true" />
        </template>
    </Column>
    <Column field="inventoryStatus" header="Status" sortable style="min-width: 12rem">
        <template #body="slotProps">
            <Tag :value="slotProps.data.inventoryStatus" :severity="getStatusLabel(slotProps.data.inventoryStatus)" />
        </template>
    </Column>
    <Column :exportable="false" style="min-width: 12rem">
        <template #body="slotProps">
            <Button icon="pi pi-pencil" variant="outlined" rounded class="mr-2" @click="editProduct(slotProps.data)" />
            <Button icon="pi pi-trash" variant="outlined" rounded severity="danger" @click="confirmDeleteProduct(slotProps.data)" />
        </template>
    </Column>
</DataTable>

Accessibility
Screen Reader
DataTable uses a table element whose attributes can be extended with the tableProps option. This property allows passing aria roles and attributes like aria-label and aria-describedby to define the table for readers. Default role of the table is table. Header, body and footer elements use rowgroup, rows use row role, header cells have columnheader and body cells use cell roles. Sortable headers utilizer aria-sort attribute either set to "ascending" or "descending".

Built-in checkbox and radiobutton components for row selection use checkbox and radiobutton. The label to describe them is retrieved from the aria.selectRow and aria.unselectRow properties of the locale API. Similarly header checkbox uses selectAll and unselectAll keys. When a row is selected, aria-selected is set to true on a row.

The element to expand or collapse a row is a button with aria-expanded and aria-controls properties. Value to describe the buttons is derived from aria.expandRow and aria.collapseRow properties of the locale API.

The filter menu button use aria.showFilterMenu and aria.hideFilterMenu properties as aria-label in addition to the aria-haspopup, aria-expanded and aria-controls to define the relation between the button and the overlay. Popop menu has dialog role with aria-modal as focus is kept within the overlay. The operator dropdown use aria.filterOperator and filter constraints dropdown use aria.filterConstraint properties. Buttons to add rules on the other hand utilize aria.addRule and aria.removeRule properties. The footer buttons similarly use aria.clear and aria.apply properties. filterInputProps of the Column component can be used to define aria labels for the built-in filter components, if a custom component is used with templating you also may define your own aria labels as well.

Editable cells use custom templating so you need to manage aria roles and attributes manually if required. The row editor controls are button elements with aria.editRow, aria.cancelEdit and aria.saveEdit used for the aria-label.

Paginator is a standalone component used inside the DataTable, refer to the paginator for more information about the accessibility features.

Keyboard Support
Any button element inside the DataTable used for cases like filter, row expansion, edit are tabbable and can be used with space and enter keys.

Sortable Headers Keyboard Support
Key	Function
tab	Moves through the headers.
enter	Sorts the column.
space	Sorts the column.
Filter Menu Keyboard Support
Key	Function
tab	Moves through the elements inside the popup.
escape	Hides the popup.
Selection Keyboard Support
Key	Function
tab	Moves focus to the first selected row, if there is none then first row receives the focus.
up arrow	Moves focus to the previous row.
down arrow	Moves focus to the next row.
enter	Toggles the selected state of the focused row depending on the metaKeySelection setting.
space	Toggles the selected state of the focused row depending on the metaKeySelection setting.
home	Moves focus to the first row.
end	Moves focus to the last row.
shift + down arrow	Moves focus to the next row and toggles the selection state.
shift + up arrow	Moves focus to the previous row and toggles the selection state.
shift + space	Selects the rows between the most recently selected row and the focused row.
control + shift + home	Selects the focused rows and all the options up to the first one.
control + shift + end	Selects the focused rows and all the options down to the last one.
control + a	Selects all rows.