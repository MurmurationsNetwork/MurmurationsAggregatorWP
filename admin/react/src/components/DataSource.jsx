import { schemas } from '../data/schemas'
import { useEffect, useState } from 'react'
import { getCountries } from '../utils/getCountries'
import PropTypes from 'prop-types'

export default function DataSource({
  formData,
  handleInputChange,
  selectedCountry,
  setSelectedCountry
}) {
  const [countries, setCountries] = useState([])

  useEffect(() => {
    getCountries().then(countries => {
      const countryKeys = Object.keys(countries)
      setCountries(countryKeys)
    })
  }, [])

  const handleCountryChange = event => {
    const selected = Array.from(event.target.options)
      .filter(option => option.selected)
      .map(option => option.value)

    setSelectedCountry(selected)
  }

  return (
    <div>
      <h3 className="mt-4 text-lg">Node Selection</h3>
      <div className="mt-2 border-2 border-dotted border-red-500 p-4">
        <div className="mb-4">
          <label
            className="mb-2 block font-bold text-gray-700"
            htmlFor="data_url"
          >
            Source Index
          </label>
          <select
            id="data_url"
            name="data_url"
            value={formData.data_url}
            onChange={handleInputChange}
            className="w-full rounded border px-3 py-2"
          >
            <option value="https://test-index.murmurations.network/v2/nodes">
              https://test-index.murmurations.network/v2/nodes
            </option>
            <option value="https://index.murmurations.network/v2/nodes">
              https://index.murmurations.network/v2/nodes
            </option>
          </select>
          <div className="mt-1">
            Select the test or production index to find nodes for your map or
            directory
          </div>
        </div>
        <div className="mb-4">
          <label
            className="mb-2 block font-bold text-gray-700"
            htmlFor="schema"
          >
            Schema
          </label>
          <select
            id="schema"
            name="schema"
            value={formData.schema}
            onChange={handleInputChange}
            className="w-full rounded border px-3 py-2"
          >
            {schemas.map(schema => (
              <option key={schema.name} value={schema.name}>
                {schema.title}
              </option>
            ))}
          </select>
          <div className="mt-1">
            Select a schema to specify the type of nodes you want to display
          </div>
        </div>
        <p className="mb-4 mt-8 text-base font-bold">
          Filter the number of nodes returned from the index using the optional
          fields below
        </p>
        <div className="mb-4">
          <label className="mb-2 block font-bold text-gray-700" htmlFor="name">
            Name
          </label>
          <input
            type="text"
            id="name"
            name="name"
            value={formData.name}
            onChange={handleInputChange}
            className="w-full rounded border px-3 py-2"
          />
          <p className="mt-1">Search for nodes with a specific name</p>
        </div>
        <div className="mb-4">
          <label className="mb-2 block font-bold text-gray-700" htmlFor="lat">
            Latitude
          </label>
          <input
            type="number"
            id="lat"
            name="lat"
            min="-90"
            max="90"
            step="any"
            value={formData.lat}
            onChange={handleInputChange}
            className="w-full rounded border px-3 py-2"
          />
          <p className="mt-1">Search for nodes near a specific latitude</p>
        </div>
        <div className="mb-4">
          <label className="mb-2 block font-bold text-gray-700" htmlFor="lon">
            Longitude
          </label>
          <input
            type="number"
            id="lon"
            name="lon"
            min="-180"
            max="180"
            step="any"
            value={formData.lon}
            onChange={handleInputChange}
            className="w-full rounded border px-3 py-2"
          />
          <p className="mt-1">Search for nodes near a specific longitude</p>
        </div>
        <div className="mb-4">
          <label className="mb-2 block font-bold text-gray-700" htmlFor="range">
            Range (i.e. 25km, 15mi)
          </label>
          <input
            type="text"
            id="range"
            name="range"
            value={formData.range}
            onChange={handleInputChange}
            className="w-full rounded border px-3 py-2"
          />
          <p className="mt-1">
            Search for nodes within a specific distance from the latitude and
            longitude specified above
          </p>
        </div>
        <div className="mb-4">
          <label
            className="mb-2 block font-bold text-gray-700"
            htmlFor="locality"
          >
            Locality
          </label>
          <input
            type="text"
            id="locality"
            name="locality"
            value={formData.locality}
            onChange={handleInputChange}
            className="w-full rounded border px-3 py-2"
          />
          <p className="mt-1">
            Search for nodes which list a specific locality (e.g., Paris,
            London, San Francisco, etc.)
          </p>
        </div>
        <div className="mb-4">
          <label
            className="mb-2 block font-bold text-gray-700"
            htmlFor="region"
          >
            Region
          </label>
          <input
            type="text"
            id="region"
            name="region"
            value={formData.region}
            onChange={handleInputChange}
            className="w-full rounded border px-3 py-2"
          />
          <p className="mt-1">
            Search for nodes which list a specific region (e.g., Île-de-France,
            Greater London, California, etc.)
          </p>
        </div>
        <div className="mb-4">
          <label
            className="mb-2 block font-bold text-gray-700"
            htmlFor="country"
          >
            Country
          </label>
          <select
            multiple={true}
            id="country"
            name="country"
            value={selectedCountry}
            onChange={handleCountryChange}
            className="w-full rounded border px-3 py-2"
          >
            {countries.map(country => (
              <option key={country} value={country}>
                {country}
              </option>
            ))}
          </select>
          <p className="mt-1">
            Search for nodes which list a specific country code
          </p>
        </div>
        <div className="mb-4">
          <label className="mb-2 block font-bold text-gray-700" htmlFor="tags">
            Tags
          </label>
          <input
            type="text"
            id="tags"
            name="tags"
            value={formData.tags}
            onChange={handleInputChange}
            className="w-full rounded border px-3 py-2"
          />
          <p className="mt-1">
            Search for nodes which list specific tags (use commas to search for
            multiple tags)
          </p>
        </div>
        <div className="mb-4">
          <label
            className="mb-2 block font-bold text-gray-700"
            htmlFor="tags_filter"
          >
            All Tags
          </label>
          <input
            type="checkbox"
            id="tags_filter"
            name="tags_filter"
            checked={formData.tags_filter === 'and'}
            onChange={handleInputChange}
            className="mr-2"
          />
          <p className="mt-1">
            Only return nodes with all of the tags specified above
          </p>
        </div>
        <div className="mb-4">
          <label
            className="mb-2 block font-bold text-gray-700"
            htmlFor="tags_exact"
          >
            Tags Exact
          </label>
          <input
            type="checkbox"
            id="tags_exact"
            name="tags_exact"
            checked={formData.tags_exact}
            onChange={handleInputChange}
            className="mr-2"
          />
          <p className="mt-1">
            Only return nodes with exact matches (turns off fuzzy matching)
          </p>
        </div>
        <div className="mb-4">
          <label
            className="mb-2 block font-bold text-gray-700"
            htmlFor="primary_url"
          >
            Primary URL
          </label>
          <input
            type="text"
            id="primary_url"
            name="primary_url"
            value={formData.primary_url}
            onChange={handleInputChange}
            className="w-full rounded border px-3 py-2"
          />
          <p className="mt-1">
            Search for nodes with a specific primary URL (don’t include http or
            www, e.g., <code>my.org</code> or <code>some-host.net/my-org</code>)
          </p>
        </div>
      </div>
    </div>
  )
}

DataSource.propTypes = {
  formData: PropTypes.shape({
    data_url: PropTypes.string,
    schema: PropTypes.string,
    name: PropTypes.string,
    lat: PropTypes.string,
    lon: PropTypes.string,
    range: PropTypes.string,
    locality: PropTypes.string,
    region: PropTypes.string,
    tags: PropTypes.string,
    tags_filter: PropTypes.string,
    tags_exact: PropTypes.bool,
    primary_url: PropTypes.string
  }),
  handleInputChange: PropTypes.func.isRequired,
  selectedCountry: PropTypes.array.isRequired,
  setSelectedCountry: PropTypes.func.isRequired
}
