import PropTypes from 'prop-types'

function MapSettings({ formData, handleInputChange, isEdit = false }) {
  return (
    <div>
      <h2 className="text-xl">Map Settings</h2>
      <div className="border-2 border-dotted border-red-500 p-4 mt-2">
        <div className="mb-4">
          <label
            className="block text-gray-700 font-bold mb-2"
            htmlFor="map_name"
          >
            Map Name
          </label>
          <input
            type="text"
            id="map_name"
            name="map_name"
            value={formData.map_name}
            onChange={handleInputChange}
            className="w-full border rounded py-2 px-3"
            required={true}
          />
        </div>
        <div className="mb-4">
          <label
            className="block text-gray-700 font-bold mb-2"
            htmlFor="map_name"
          >
            Map Center Latitude (Default is Paris)
          </label>
          <input
            type="number"
            id="map_center_lat"
            name="map_center_lat"
            value={formData.map_center_lat}
            onChange={handleInputChange}
            className="w-full border rounded py-2 px-3"
            min={-90}
            max={90}
            step="any"
          />
        </div>
        <div className="mb-4">
          <label
            className="block text-gray-700 font-bold mb-2"
            htmlFor="map_name"
          >
            Map Center Longitude (Default is Paris)
          </label>
          <input
            type="number"
            id="map_center_lon"
            name="map_center_lon"
            value={formData.map_center_lon}
            onChange={handleInputChange}
            className="w-full border rounded py-2 px-3"
            min={-180}
            max={180}
            step="any"
          />
        </div>
        <div className="mb-4">
          <label
            className="block text-gray-700 font-bold mb-2"
            htmlFor="map_name"
          >
            Map Center Scale (Default is 5)
          </label>
          <input
            type="number"
            id="map_scale"
            name="map_scale"
            value={formData.map_scale}
            onChange={handleInputChange}
            className="w-full border rounded py-2 px-3"
            min={0}
            max={20}
            step="any"
          />
        </div>
        {!isEdit && (
          <div className="mb-4">
            <label
              className="block text-gray-700 font-bold mb-2"
              htmlFor="tag_slug"
            >
              Tag Slug (The tag will be applied to all nodes)
            </label>
            <input
              type="text"
              id="tag_slug"
              name="tag_slug"
              value={formData.tag_slug}
              onChange={handleInputChange}
              className="w-full border rounded py-2 px-3"
              required={true}
            />
          </div>
        )}
      </div>
    </div>
  )
}

MapSettings.propTypes = {
  formData: PropTypes.object.isRequired,
  handleInputChange: PropTypes.func.isRequired,
  isEdit: PropTypes.bool
}

export default MapSettings
