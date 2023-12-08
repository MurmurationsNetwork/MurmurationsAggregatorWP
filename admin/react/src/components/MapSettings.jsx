import PropTypes from 'prop-types'

export default function MapSettings({ formData, handleInputChange }) {
  return (
    <div>
      <h3 className="text-lg">Map Settings</h3>
      <div className="border-2 border-dotted border-red-500 p-4 mt-2">
        <div className="mb-4">
          Use{' '}
          <a
            className="text-blue-500 underline"
            href="https://latlong.net"
            target="_blank"
            rel="noreferrer"
          >
            LatLong.net
          </a>{' '}
          to pick a location, enter coordinates with decimals (e.g., 48.86124)
        </div>

        <div className="mb-4">
          <label
            className="block text-gray-700 font-bold mb-2"
            htmlFor="map_name"
          >
            Map Center Latitude (default is Paris, France)
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
            Map Center Longitude (default is Paris, France)
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
            Map Scale (default is 5)
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
          <div className="mt-1">1 = the entire globe, 18 = maximum zoom in</div>
        </div>
      </div>
    </div>
  )
}

MapSettings.propTypes = {
  formData: PropTypes.object.isRequired,
  handleInputChange: PropTypes.func.isRequired
}
